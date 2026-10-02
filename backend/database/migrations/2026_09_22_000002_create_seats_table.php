<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->string('row', 10);
            $table->unsignedSmallInteger('number');
            $table->string('status', 20)->default('available');
            $table->timestamps();

            $table->unique(['section_id', 'row', 'number']);
            // read path for the seat map (Day 4) and for checking availability (Day 5)
            $table->index(['section_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};
