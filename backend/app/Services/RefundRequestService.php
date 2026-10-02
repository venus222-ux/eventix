<?php

namespace App\Services;

use App\Mail\RefundRequestMail;
use App\Models\Order;
use App\Models\RefundRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Refund request pipeline:
 *   form (reason + message) -> context snapshot -> decision -> auto refund OR human review queue.
 * Every request keeps its snapshot, decision source and note (audit trail).
 */
class RefundRequestService
{
    public const REASONS = [
        'cannot_attend' => "I can't attend the event",
        'event_changed' => 'The event date or details changed',
        'duplicate_purchase' => 'I bought the tickets twice',
        'bought_by_mistake' => 'I bought the wrong seats or tickets',
        'other' => 'Other',
    ];

    public function __construct(private RefundService $refunds) {}

    /** Called by the customer. Returns the request (approved right away, or pending review). */
    public function submit(Order $order, User $user, string $reason, string $message): RefundRequest
    {
        $request = DB::transaction(function () use ($order, $user, $reason, $message) {
            // Lock the order so a double click cannot create two requests
            $locked = Order::with(['event', 'items'])->whereKey($order->id)->lockForUpdate()->firstOrFail();

            abort_unless($locked->isRefundableByUser(), 409, 'This order can no longer be refunded');
            abort_if(
                $locked->refundRequests()->where('status', 'pending')->exists(),
                409,
                'A refund request for this order is already being reviewed'
            );

            $snapshot = $this->snapshot($locked, $user);
            $decision = $this->decide($locked, $snapshot);

            return RefundRequest::create([
                'order_id' => $locked->id,
                'user_id' => $user->id,
                'reason' => $reason,
                'message' => $message,
                'status' => 'pending',
                'snapshot' => $snapshot + [
                    'recommendation' => $decision['action'],
                    'recommendation_note' => $decision['note'],
                ],
            ]);
        });

        if ($request->snapshot['recommendation'] === 'approve') {
            return $this->approve($request, 'auto', null, $request->snapshot['recommendation_note']);
        }

        Mail::to($user->email)->queue(new RefundRequestMail($request));

        return $request;
    }

    /** Issues the Stripe refund, releases the seats (RefundService) and notifies the customer. */
    public function approve(RefundRequest $request, string $by, ?User $admin = null, ?string $note = null): RefundRequest
    {
        abort_unless($request->status === 'pending', 409, 'This request was already decided');

        $this->refunds->refund($request->order);

        $request->update([
            'status' => 'approved',
            'decided_by' => $by,
            'decided_by_user_id' => $admin?->id,
            'decision_note' => $note,
            'decided_at' => now(),
        ]);

        Mail::to($request->user->email)->queue(new RefundRequestMail($request));

        return $request;
    }

    public function decline(RefundRequest $request, User $admin, string $note): RefundRequest
    {
        abort_unless($request->status === 'pending', 409, 'This request was already decided');

        $request->update([
            'status' => 'declined',
            'decided_by' => 'admin',
            'decided_by_user_id' => $admin->id,
            'decision_note' => $note,
            'decided_at' => now(),
        ]);

        Mail::to($request->user->email)->queue(new RefundRequestMail($request));

        return $request;
    }

    /** Real data about the order at request time (kept for the audit trail and the reviewer). */
    private function snapshot(Order $order, User $user): array
    {
        return [
            'amount_cents' => $order->total_cents,
            'tickets' => $order->items->count(),
            'hours_since_purchase' => $order->paid_at ? (int) $order->paid_at->diffInHours(now()) : null,
            'hours_until_event' => (int) now()->diffInHours($order->event->starts_at, false),
            'approved_refunds_30d' => RefundRequest::where('user_id', $user->id)
                ->where('status', 'approved')
                ->where('decided_at', '>=', now()->subDays(30))
                ->count(),
        ];
    }

    /**
     * Deterministic rules, no LLM: the customer is already inside the refund window
     * (Order::isRefundableByUser), so the only question is auto-approve or send to a human.
     */
    private function decide(Order $order, array $snapshot): array
    {
        $maxCents = (int) config('eventix.refund_auto_approve_max_cents', 50000);
        $maxPerMonth = (int) config('eventix.refund_auto_approve_max_per_30d', 3);

        if ($order->total_cents > $maxCents) {
            return ['action' => 'review', 'note' => 'Amount above the automatic approval limit'];
        }

        if ($snapshot['approved_refunds_30d'] >= $maxPerMonth) {
            return ['action' => 'review', 'note' => 'Several refunds in the last 30 days'];
        }

        return ['action' => 'approve', 'note' => 'Within policy and under the automatic approval limit'];
    }
}