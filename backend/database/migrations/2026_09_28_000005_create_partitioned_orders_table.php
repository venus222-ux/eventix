<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS "uuid-ossp";');

        // Tabela orders partitionata după event_id
        DB::statement('
            CREATE TABLE orders (
                id UUID NOT NULL DEFAULT gen_random_uuid(),
                user_id BIGINT NOT NULL,
                event_id BIGINT NOT NULL,
                total_cents INT NOT NULL,
                status VARCHAR(50) NOT NULL DEFAULT \'pending\',
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NULL,
                PRIMARY KEY (id, event_id)
            ) PARTITION BY HASH (event_id);
        ');

        // Creare 4 partiții de bază pentru echilibrare
        for ($i = 0; $i < 4; $i++) {
            DB::statement("CREATE TABLE orders_p{$i} PARTITION OF orders FOR VALUES WITH (MODULUS 4, REMAINDER {$i});");
        }

        // Tabela order_items optimizată fără foreign keys
   DB::statement('
    CREATE TABLE order_items (
        id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
        order_id UUID NOT NULL,
        seat_id BIGINT NOT NULL,
        unit_price_cents INT NOT NULL, -- Modificat din price_cents în unit_price_cents
        created_at TIMESTAMP(0) WITHOUT TIME ZONE NULL,
        updated_at TIMESTAMP(0) WITHOUT TIME ZONE NULL
    );
');

        DB::statement('CREATE INDEX idx_order_items_order_id ON order_items(order_id);');
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS order_items;');
        DB::statement('DROP TABLE IF EXISTS orders;');
    }
};