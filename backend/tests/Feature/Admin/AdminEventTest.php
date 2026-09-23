<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminEventTest extends TestCase
{
    use RefreshDatabase;

private function headersFor(string $role): array
{
    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    $roleModel = Role::findOrCreate($role, 'api');
    $user = User::factory()->create();
    $user->assignRole($roleModel);

    // Add the 'type' => 'access' claim expected by JwtMiddleware
    $token = JWTAuth::claims(['type' => 'access'])->fromUser($user);

    return [
        'Authorization' => 'Bearer ' . $token,
        'Accept'        => 'application/json',
    ];
}

    private function payload(array $override = []): array
    {
        return array_merge([
            'title'       => 'Summer Fest',
            'description' => 'Three days of music',
            'category_id' => Category::factory()->create()->id,
            'venue_id'    => Venue::factory()->create()->id,
            'starts_at'   => now()->addWeek()->toDateTimeString(),
            'ends_at'     => now()->addWeek()->addHours(4)->toDateTimeString(),
            'status'      => 'published',
        ], $override);
    }

    public function test_guest_gets_401(): void
    {
        $this->getJson('/api/admin/events')->assertStatus(401);
    }

    public function test_regular_user_gets_403(): void
    {
        $this->getJson('/api/admin/events', $this->headersFor('user'))->assertStatus(403);
    }

    public function test_admin_creates_event_with_banner(): void
    {
        Storage::fake('public');

        $res = $this->post('/api/admin/events', $this->payload([
            'banner' => UploadedFile::fake()->image('banner.jpg', 1200, 600),
        ]), $this->headersFor('admin'));

        $res->assertCreated()->assertJsonPath('data.slug', 'summer-fest');

        $event = Event::firstOrFail();
        Storage::disk('public')->assertExists($event->banner_path);
    }

    public function test_slug_is_unique_for_same_title(): void
    {
        $headers = $this->headersFor('admin');
        $this->postJson('/api/admin/events', $this->payload(), $headers)->assertCreated();
        $this->postJson('/api/admin/events', $this->payload(), $headers)
            ->assertCreated()->assertJsonPath('data.slug', 'summer-fest-2');
    }

public function test_end_must_be_after_start(): void
{
    // Remove $this->withoutExceptionHandling(); if present here
    $this->postJson('/api/admin/events', $this->payload([
        'ends_at' => now()->addDay()->toDateTimeString(),
    ]), $this->headersFor('admin'))
        ->assertStatus(422)
        ->assertJsonValidationErrors('ends_at');
}
    public function test_update_validates_end_against_existing_start(): void
    {
        $event = Event::factory()->create();

        $this->putJson("/api/admin/events/{$event->id}", [
            'ends_at' => $event->starts_at->copy()->subHour()->toDateTimeString(),
        ], $this->headersFor('admin'))->assertStatus(422)->assertJsonValidationErrors('ends_at');
    }

    public function test_replacing_banner_deletes_old_file(): void
    {
        Storage::fake('public');
        $headers = $this->headersFor('admin');
        $event = Event::factory()->create();

        $this->post("/api/admin/events/{$event->id}/banner", [
            'banner' => UploadedFile::fake()->image('a.jpg'),
        ], $headers)->assertOk();
        $first = $event->fresh()->banner_path;

        $this->post("/api/admin/events/{$event->id}/banner", [
            'banner' => UploadedFile::fake()->image('b.png'),
        ], $headers)->assertOk();

        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($event->fresh()->banner_path);
    }

 public function test_rejects_non_image_banner(): void
{
    $event = Event::factory()->create();

    $this->postJson("/api/admin/events/{$event->id}/banner", [
        'banner' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
    ], $this->headersFor('admin'))->assertStatus(422)->assertJsonValidationErrors('banner');
}
    public function test_soft_delete_and_restore(): void
    {
        $headers = $this->headersFor('admin');
        $event = Event::factory()->create();

        $this->deleteJson("/api/admin/events/{$event->id}", [], $headers)->assertOk();
        $this->assertSoftDeleted($event);

        $this->patchJson("/api/admin/events/{$event->id}/restore", [], $headers)->assertOk();
        $this->assertNotSoftDeleted($event);
    }

    public function test_index_filters_by_status(): void
    {
        Event::factory()->count(2)->create();
        Event::factory()->draft()->create();

        $this->getJson('/api/admin/events?status=draft', $this->headersFor('admin'))
            ->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_cannot_delete_category_or_venue_with_events(): void
    {
        $headers = $this->headersFor('admin');
        $event = Event::factory()->create();

        $this->deleteJson("/api/admin/categories/{$event->category_id}", [], $headers)->assertStatus(409);
        $this->deleteJson("/api/admin/venues/{$event->venue_id}", [], $headers)->assertStatus(409);
    }
}