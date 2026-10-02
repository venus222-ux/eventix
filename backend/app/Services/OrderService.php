<?php

namespace App\Services;

use App\Enums\SeatStatus;
use App\Exceptions\SeatUnavailableException;
use App\Jobs\ReleaseExpiredReservationJob;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Seat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public const HOLD_SECONDS = 600;

    public function __construct(
        private ReservationService $reservations,
        private PaymentService $payments,
    ) {}

    /** @return array{0: Order, 1: string} [order, client_secret] */
    public function checkout(int $userId, Event $event, array $seatIds): array
    {
        $seats = Seat::whereIn('id', $seatIds)
            ->whereHas('section', fn ($q) => $q->where('venue_id', $event->venue_id))
            ->with('section')
            ->get();

        if ($seats->count() !== count(array_unique($seatIds))) {
            throw new SeatUnavailableException("One or more seats do not belong to this event's venue.");
        }

        if ($seats->contains(fn (Seat $s) => $s->status !== SeatStatus::Available)) {
            throw new SeatUnavailableException('One or more seats are no longer available.');
        }

        // Server-side pricing: event override -> section default -> event base price
        $event->loadMissing('sections');
        $unit = fn (Seat $s) => $event->priceFor($s->section);
        $totalCents = $seats->sum($unit);

        // Stripe minimum is 0.50 EUR. This is a configuration problem, not a seat conflict,
        // so it returns 422 (not 409) and nothing is reserved.
        if ($totalCents < 50) {
            throw ValidationException::withMessages([
                'seat_ids' => 'This event has no ticket price set for the selected section(s).',
            ]);
        }

        if (! $this->reservations->reserveSeats($event->id, $seatIds, self::HOLD_SECONDS)) {
            throw new SeatUnavailableException('Someone else is holding one of these seats.');
        }

        $order = null;

        try {
            $order = DB::transaction(function () use ($userId, $event, $seats, $totalCents, $unit) {
                $order = Order::create([
                    'user_id' => $userId,
                    'event_id' => $event->id,
                    'total_cents' => $totalCents,
                    'vat_rate' => config('eventix.vat_rate', 19),
                    'status' => 'pending',
                    'expires_at' => now()->addSeconds(self::HOLD_SECONDS),
                ]);

                OrderItem::insert(
                    $seats->map(fn (Seat $s) => [
                        'id' => (string) Str::uuid(),
                        'order_id' => $order->id,
                        'seat_id' => $s->id,
                        'unit_price_cents' => $unit($s),
                    ])->all()
                );

                return $order;
            });

            $order->load('user');

            $intent = $this->payments->createIntent($order);

            $order->update(['payment_intent_id' => $intent->id]);
        } catch (\Throwable $e) {
            $order?->update(['status' => 'cancelled']);
            $this->reservations->releaseSeats($event->id, $seatIds);

            throw $e;
        }

        ReleaseExpiredReservationJob::dispatch($order->id, $event->id, $seatIds)
            ->delay(now()->addSeconds(self::HOLD_SECONDS + 30));

        // 'user' is loaded so OrderResource exposes the billing fields to the checkout page
        return [$order->load(['user', 'items.seat.section']), $intent->client_secret];
    }

    /** Used by the expiry job and the user's "cancel" button. */
    public function cancelPending(Order $order): void
    {
        if ($order->status !== 'pending') {
            return;
        }

        if ($order->payment_intent_id) {
            // Money may have landed a moment ago: let the webhook win.
            if ($this->payments->retrieve($order->payment_intent_id)->status === 'succeeded') {
                return;
            }

            $this->payments->cancelIntent($order->payment_intent_id);
        }

        $order->update(['status' => 'cancelled']);

        $this->reservations->releaseSeats(
            $order->event_id,
            $order->items()->pluck('seat_id')->all()
        );
    }
}