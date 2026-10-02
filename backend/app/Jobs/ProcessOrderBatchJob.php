<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProcessOrderBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $ordersData) {}

    public function handle(): void
    {
        $ordersToInsert = [];
        $itemsToInsert = [];
        $now = now();

        foreach ($this->ordersData as $data) {
            $orderUuid = (string) Str::uuid();
            
            $ordersToInsert[] = [
                'id' => $orderUuid,
                'user_id' => $data['user_id'],
                'event_id' => $data['event_id'],
                'total_cents' => $data['total_cents'],
                'status' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ];

            foreach ($data['items'] as $item) {
                $itemsToInsert[] = [
                    'id' => (string) Str::uuid(),
                    'order_id' => $orderUuid,
                    'seat_id' => $item['seat_id'],
                    'price_cents' => $item['price_cents'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::transaction(function () use ($ordersToInsert, $itemsToInsert) {
            DB::table('orders')->insert($ordersToInsert);
            DB::table('order_items')->insert($itemsToInsert);
        });
    }
}