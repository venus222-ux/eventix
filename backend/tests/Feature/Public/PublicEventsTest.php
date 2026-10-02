<?php

namespace Tests\Feature\Public;

use App\Models\Category;
use App\Models\Event;
use App\Services\SeatMapGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_only_returns_published_events(): void
    {
        Event::factory()->count(2)->create(); // published (factory default)
        Event::factory()->draft()->create();

        $this->getJson('/api/events')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_filters_by_category_slug(): void
    {
        $rock = Category::factory()->create(['name' => 'Rock', 'slug' => 'rock']);
        $jazz = Category::factory()->create(['name' => 'Jazz', 'slug' => 'jazz']);

        Event::factory()->create(['category_id' => $rock->id]);
        Event::factory()->create(['category_id' => $jazz->id]);

        $this->getJson('/api/events?category=rock')
            ->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.category.slug', 'rock');
    }

    public function test_show_by_slug_returns_404_for_draft(): void
    {
        $event = Event::factory()->draft()->create();

        $this->getJson("/api/events/{$event->slug}")->assertNotFound();
    }

    public function test_show_by_slug_returns_published_event(): void
    {
        $event = Event::factory()->create();

        $this->getJson("/api/events/{$event->slug}")
            ->assertOk()->assertJsonPath('data.slug', $event->slug);
    }

    public function test_seats_endpoint_groups_by_section(): void
    {
        $event = Event::factory()->create();

        app(SeatMapGenerator::class)->generate($event->venue, [
            ['name' => 'VIP', 'rows' => 1, 'seats_per_row' => 2, 'price_cents' => 50000],
        ]);

        $res = $this->getJson("/api/events/{$event->slug}/seats")->assertOk();
        $res->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'VIP')
            ->assertJsonCount(2, 'data.0.seats');
    }
}
