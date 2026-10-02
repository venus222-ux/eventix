<?php

namespace App\Services;

use App\Models\Order;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\StripeClient;

class PaymentService
{
    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    public function createIntent(Order $order): PaymentIntent
    {
        return $this->stripe->paymentIntents->create([
            'amount' => $order->total_cents,
            'currency' => 'eur',
            'automatic_payment_methods' => ['enabled' => true],
            'receipt_email' => $order->user->email,
            'description' => "Order {$order->id}",
            'metadata' => [
                'order_id' => $order->id,
                'event_id' => $order->event_id,
            ],
        ], [
            'idempotency_key' => "pi_order_{$order->id}",
        ]);
    }

    public function retrieve(string $id): PaymentIntent
    {
        return $this->stripe->paymentIntents->retrieve($id);
    }

    public function cancelIntent(string $id): void
    {
        try {
            $this->stripe->paymentIntents->cancel($id);
        } catch (ApiErrorException $e) {
            // already succeeded / already cancelled
            report($e);
        }
    }

    public function refund(Order $order): void
    {
        $this->stripe->refunds->create(
            ['payment_intent' => $order->payment_intent_id],
            ['idempotency_key' => "refund_{$order->id}"]
        );
    }
}