<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Price of a section for ONE event (optional override).
        // Fallback chain: this row -> sections.price_cents -> events.price_cents
        Schema::create('event_section_prices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('event_id')->constrained()->cascadeOnDelete();
            $t->foreignId('section_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('price_cents');
            $t->unique(['event_id', 'section_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_section_prices');
    }
};
