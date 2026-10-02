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
                $t->string('payment_intent_id')->nullable()->unique();
            }
            if (! Schema::hasColumn('orders', 'invoice_number')) {
                $t->string('invoice_number')->nullable()->unique();
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
        });

        DB::statement('CREATE SEQUENCE IF NOT EXISTS invoice_seq START 1');
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $t) {
            $t->dropColumn(['payment_intent_id', 'invoice_number', 'expires_at', 'paid_at', 'refunded_at']);
        });
        DB::statement('DROP SEQUENCE IF EXISTS invoice_seq');
    }
};