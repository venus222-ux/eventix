<?php

namespace Tests\Feature;

use App\Enums\SeatStatus;
use App\Models\Event;
use App\Models\Seat;
use App\Models\User;
use App\Models\Venue;
use App\Services\SeatMapGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private function authHeaders(): array
    {
        $user = User::factory()->create();

        return ['Authorization' => 'Bearer '.JWTAuth::fromUser($user), 'Accept' => 'application/json'];
    }

    private function seatsFor(Event $event, int $count = 2): array
    {
        app(SeatMapGenerator::class)->generate($event->venue, [
            ['name' => 'VIP', 'rows' => 1, 'seats_per_row' => 5, 'price_cents' => 20000],
        ]);

        return Seat::orderBy('id')->take($count)->pluck('id')->all();
    }

    public function test_guest_cannot_order(): void
    {
        $event = Event::factory()->create();
        $seatIds = $this->seatsFor($event);

        $this->postJson('/api/orders', ['event_id' => $event->id, 'seat_ids' => $seatIds])
            ->assertStatus(401);
    }

    public function test_authenticated_user_can_buy_available_seats(): void
    {
        $event = Event::factory()->create();
        $seatIds = $this->seatsFor($event, 2);

        $res = $this->postJson('/api/orders', [
            'event_id' => $event->id,
            'seat_ids' => $seatIds,
        ], $this->authHeaders());

        $res->assertCreated()
            ->assertJsonPath('data.total_cents', 40000)
            ->assertJsonCount(2, 'data.items');

        foreach ($seatIds as $id) {
            $this->assertDatabaseHas('seats', ['id' => $id, 'status' => SeatStatus::Sold->value]);
        }
    }

    public function test_cannot_buy_an_already_sold_seat(): void
    {
        $event = Event::factory()->create();
        $seatIds = $this->seatsFor($event, 1);
        $headers = $this->authHeaders();

        $this->postJson('/api/orders', ['event_id' => $event->id, 'seat_ids' => $seatIds], $headers)
            ->assertCreated();

        $this->postJson('/api/orders', ['event_id' => $event->id, 'seat_ids' => $seatIds], $headers)
            ->assertStatus(409);
    }

    public function test_cannot_buy_a_seat_from_a_different_venue(): void
    {
        $eventA = Event::factory()->create();
        $eventB = Event::factory()->create(['venue_id' => Venue::factory()]);
        $seatIdsForB = $this->seatsFor($eventB, 1);

        $this->postJson('/api/orders', [
            'event_id' => $eventA->id,
            'seat_ids' => $seatIdsForB,
        ], $this->authHeaders())->assertStatus(409);
    }
}
