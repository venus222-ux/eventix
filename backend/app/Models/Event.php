<?php

namespace App\Models;

use App\Enums\EventStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'description', 'category_id', 'venue_id', 'created_by',
        'starts_at', 'ends_at', 'banner_path', 'status', 'price_cents',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => EventStatus::class,
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Per-event price overrides, one row per section (pivot.price_cents). */
    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(Section::class, 'event_section_prices')
            ->withPivot('price_cents');
    }

    /**
     * Single source of truth for what a seat in $section costs for this event.
     * 1) per-event override  2) section default  3) event base price
     */
    public function priceFor(Section $section): int
    {
        $this->loadMissing('sections');

        $override = $this->sections->firstWhere('id', $section->id)?->pivot->price_cents;

        return (int) ($override ?: $section->price_cents ?: $this->price_cents);
    }

    /**
     * @param  array<int|string, int|string|null>  $prices  section_id => cents (empty / 0 = no override)
     */
    public function syncSectionPrices(array $prices): void
    {
        // Only sections of this event's venue are accepted
        $valid = Section::where('venue_id', $this->venue_id)->pluck('id')->all();

        $sync = [];
        foreach ($prices as $sectionId => $cents) {
            if (in_array((int) $sectionId, $valid, true) && (int) $cents > 0) {
                $sync[(int) $sectionId] = ['price_cents' => (int) $cents];
            }
        }

        $this->sections()->sync($sync);
        $this->unsetRelation('sections');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', EventStatus::Published);
    }

    protected function bannerUrl(): Attribute
    {
        return Attribute::get(
            fn () => $this->banner_path
                ? Storage::disk(config('eventix.media_disk'))->url($this->banner_path)
                : null
        );
    }
}