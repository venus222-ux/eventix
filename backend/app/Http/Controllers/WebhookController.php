<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderFulfillmentService;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class WebhookController extends Controller
{
    public function __construct(
        private OrderService $orders,
        private OrderFulfillmentService $fulfillment,
    ) {}

    public function handleStripe(Request $request)
    {
        $payload        = $request->getContent();
        $sigHeader      = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $endpointSecret
            );
        } catch (\UnexpectedValueException | SignatureVerificationException $e) {
            Log::error(
                'Stripe Webhook Signature Failed: ' . $e->getMessage()
            );

            return response()->json(
                ['error' => 'Invalid signature'],
                400
            );
        }

        $obj = $event->data->object;

        match ($event->type) {
            'payment_intent.succeeded' => $this->handlePaymentSucceeded($obj),
            'payment_intent.canceled'  => $this->handlePaymentCanceled($obj),
            'charge.refunded'          => $this->handleChargeRefunded($obj),
            default                    => null,
        };

        return response()->json(['status' => 'success']);
    }

    private function handlePaymentSucceeded(object $paymentIntent): void
    {
        $orderId = $paymentIntent->metadata->order_id ?? null;

        $order = $orderId
            ? Order::find($orderId)
            : Order::where(
                'payment_intent_id',
                $paymentIntent->id
            )->first();

        if ($order) {
            $this->fulfillment->markPaid($order->id);
        }
    }

    private function handleChargeRefunded(object $charge): void
    {
        if (
            $order = Order::where(
                'payment_intent_id',
                $charge->payment_intent
            )->first()
        ) {
            $this->fulfillment->markRefunded($order);
        }
    }

    private function handlePaymentCanceled(object $paymentIntent): void
    {
        $order = Order::where(
            'payment_intent_id',
            $paymentIntent->id
        )->first();

        if ($order) {
            $this->orders->cancelPending($order);
        }
    }
}
