<?php

namespace App\Services;

use App\Enums\SeatStatus;
use App\Jobs\GenerateTicketPdfJob;
use App\Models\Order;
use App\Models\Seat;
use Illuminate\Support\Facades\DB;

class OrderFulfillmentService
{
    public function __construct(
        private PaymentService $payments,
        private ReservationService $reservations,
    ) {}

    public function markPaid(string $orderId): void
    {
        $needsRefund = null;

        $order = DB::transaction(function () use ($orderId, &$needsRefund) {
            $order = Order::with(['items', 'user'])
                ->lockForUpdate()
                ->find($orderId);

            if (! $order || $order->status === 'completed') {
                return null;
            }

            if ($order->status !== 'pending') {
                // Payment arrived after the order expired and its seats
                // were released, so the payment must be refunded.
                $order->update([
                    'status' => 'refunded',
                    'refunded_at' => now(),
                ]);

                $needsRefund = $order;

                return null;
            }

            Seat::whereIn('id', $order->items->pluck('seat_id'))
                ->update([
                    'status' => SeatStatus::Sold->value,
                ]);

            $n = DB::selectOne(
                "select nextval('invoice_seq') as n"
            )->n;

            $order->update([
                'status' => 'completed',
                'paid_at' => now(),
                'invoice_number' => sprintf(
                    'INV-%s-%06d',
                    now()->year,
                    $n
                ),
                // Billing address frozen at payment time (the invoice reads this)
                'billing_address' => $order->billingSnapshot(),
            ]);

            return $order;
        });

        if ($needsRefund) {
            $this->payments->refund($needsRefund);
        }

        if ($order) {
            GenerateTicketPdfJob::dispatch($order->id);
        }
    }

    /**
     * Idempotent: called by RefundService AND by the charge.refunded webhook.
     */
    public function markRefunded(Order $order): void
    {
        $eventId = $order->event_id;
        $seatIds = [];

        DB::transaction(function () use ($order, &$seatIds) {
            $locked = Order::with('items')
                ->lockForUpdate()
                ->find($order->id);

            if (! $locked || $locked->status === 'refunded') {
                return;
            }

            $seatIds = $locked->items->pluck('seat_id')->all();

            $locked->update([
                'status' => 'refunded',
                'refunded_at' => now(),
            ]);

            Seat::whereIn('id', $seatIds)
                ->update([
                    'status' => SeatStatus::Available->value,
                ]);
        });

        // The 10-minute hold from the original purchase can still be 'locked' in Redis
        // (releaseSeats is not called on payment). Without this, refunded seats stay
        // blocked and the next checkout returns 409.
        if ($seatIds) {
            $this->reservations->clearSeats($eventId, $seatIds);
        }
    }
}