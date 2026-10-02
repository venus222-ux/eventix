<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refund_requests', function (Blueprint $t) {
            $t->id();
            $t->uuid('order_id')->index();                       // orders.id is a UUID
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('reason', 40);
            $t->text('message');
            $t->string('status', 20)->default('pending')->index(); // pending | approved | declined
            $t->string('decided_by', 20)->nullable();              // auto | admin
            $t->foreignId('decided_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->text('decision_note')->nullable();
            $t->json('snapshot');                                  // audit trail: facts used for the decision
            $t->timestamp('decided_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_requests');
    }
};
