<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $t) {
            if (! Schema::hasColumn('orders', 'payment_intent_id')) {
                $t->string('payment_intent_id')->nullable()->index();
            }

            if (! Schema::hasColumn('orders', 'invoice_number')) {
                $t->string('invoice_number')->nullable()->index();
            }

            if (! Schema::hasColumn('orders', 'expires_at')) {
                $t->timestamp('expires_at')->nullable();
            }

            if (! Schema::hasColumn('orders', 'paid_at')) {
                $t->timestamp('paid_at')->nullable();
            }

            if (! Schema::hasColumn('orders', 'refunded_at')) {
                $t->timestamp('refunded_at')->nullable();
            }

            if (! Schema::hasColumn('orders', 'subtotal_cents')) {
                $t->unsignedInteger('subtotal_cents')->default(0);
            }

            if (! Schema::hasColumn('orders', 'vat_cents')) {
                $t->unsignedInteger('vat_cents')->default(0);
            }

            if (! Schema::hasColumn('orders', 'vat_rate')) {
                $t->decimal('vat_rate', 5, 2)->nullable();
            }

            if (! Schema::hasColumn('orders', 'billing_address')) {
                $t->json('billing_address')->nullable();
            }
        });

        /*
         * Existing orders:
         * total_cents = subtotal + VAT
         *
         * Exemplu:
         * total = 11900
         * VAT 19% = 1900
         * subtotal = 10000
         */
        DB::statement("
            UPDATE orders
            SET
                vat_rate = 19,
                vat_cents = ROUND(total_cents - total_cents / 1.19),
                subtotal_cents = total_cents - ROUND(total_cents - total_cents / 1.19)
            WHERE vat_rate IS NULL
        ");

        DB::statement('CREATE SEQUENCE IF NOT EXISTS invoice_seq START 1');
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $t) {
            $columns = [];

            foreach ([
                'payment_intent_id',
                'invoice_number',
                'expires_at',
                'paid_at',
                'refunded_at',
                'subtotal_cents',
                'vat_cents',
                'vat_rate',
                'billing_address',
            ] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $columns[] = $column;
                }
            }

            if ($columns) {
                $t->dropColumn($columns);
            }
        });

        DB::statement('DROP SEQUENCE IF EXISTS invoice_seq');
    }
};