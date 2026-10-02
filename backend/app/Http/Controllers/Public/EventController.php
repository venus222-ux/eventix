<?php

namespace App\Http\Controllers\Public;

use App\Enums\SeatStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Http\Resources\SectionResource;
use App\Models\Event;
use App\Models\Section;
use App\Services\ReservationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class EventController extends Controller
{
    public function __construct(
        protected ReservationService $reservations
    ) {}

    // GET /api/events
    public function index(Request $request)
    {
        $query = Event::query()
            ->published()
            ->with(['category', 'venue'])
            ->when($request->filled('category'), fn ($q) => $q->whereHas(
                'category',
                fn ($qq) => $qq->where('slug', $request->query('category'))
            ))
            ->when($request->filled('from'), fn ($q) => $q->where('starts_at', '>=', $request->query('from')))
            ->when($request->filled('to'), fn ($q) => $q->where('starts_at', '<=', $request->query('to')))
            ->orderBy('starts_at');

        $perPage = min(max((int) $request->query('per_page', 12), 1), 50);

        return EventResource::collection($query->paginate($perPage));
    }

    // GET /api/events/{event:slug}
    public function show(Event $event)
    {
        abort_unless($event->status->value === 'published', 404);

        return EventResource::make($event->load(['category', 'venue']));
    }

    // GET /api/events/{event:slug}/seats
    public function seats(Event $event)
    {
        abort_unless($event->status->value === 'published', 404);

        $cacheKey = "event_{$event->id}_seats";

        $sections = Cache::remember($cacheKey, now()->addHours(24), function () use ($event) {
            return Section::where('venue_id', $event->venue_id)
                ->with(['seats' => fn ($q) => $q->orderBy('row')->orderBy('number')])
                ->orderBy('name')
                ->get();
        });



        $ids = $sections->flatMap->seats->pluck('id')->all();
        $held = array_flip($this->reservations->lockedSeatIds($event->id, $ids));

       $sections->each(function ($sec) use ($event, $held) {
    $sec->price_cents = $sec->price_cents ?: $event->price_cents; // ?: because a 0/default section price counts as "no override"
    $sec->seats->each(function ($seat) use ($held) {
        if (isset($held[$seat->id])) $seat->status = SeatStatus::Blocked;
    });
});

        return SectionResource::collection($sections);
    }
}