<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class RefundService
{
    public function __construct(
        private PaymentService $payments,
        private OrderFulfillmentService $fulfillment,
    ) {}

    public function refund(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'completed') {
                throw new ConflictHttpException('Only paid orders can be refunded');
            }

            $this->payments->refund($locked);        // idempotency key refund_{id} already set
            $this->fulfillment->markRefunded($locked);

            return $locked;
        });
    }
}