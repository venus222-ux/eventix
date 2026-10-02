<?php

namespace Tests\Feature\Admin;

use App\Models\Venue;
use App\Services\SeatMapGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeatMapGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_correct_seat_and_section_counts(): void
    {
        $venue = Venue::factory()->create();

        $total = app(SeatMapGenerator::class)->generate($venue, [
            ['name' => 'VIP', 'rows' => 3, 'seats_per_row' => 10, 'price_cents' => 50000],
            ['name' => 'General', 'rows' => 5, 'seats_per_row' => 20, 'price_cents' => 10000],
        ]);

        $this->assertSame(30 + 100, $total);
        $this->assertDatabaseCount('sections', 2);
        $this->assertDatabaseCount('seats', 130);

        $vip = $venue->sections()->where('name', 'VIP')->first();
        $this->assertSame(30, $vip->seats()->count());
        $this->assertDatabaseHas('seats', ['section_id' => $vip->id, 'row' => 'A', 'number' => 1]);
    }

    public function test_row_labels_go_past_z(): void
    {
        $venue = Venue::factory()->create();

        app(SeatMapGenerator::class)->generate($venue, [
            ['name' => 'Big Hall', 'rows' => 27, 'seats_per_row' => 1, 'price_cents' => 1000],
        ]);

        $this->assertDatabaseHas('seats', ['row' => 'AA', 'number' => 1]);
    }
}
