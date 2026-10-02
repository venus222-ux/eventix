<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEventRequest;
use App\Http\Requests\Admin\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Services\EventService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class EventController extends Controller
{
    public function __construct(protected EventService $events) {}

    public function index(Request $request)
    {
        $query = Event::query()
            ->with(['category', 'venue'])
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->query('q').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->query('category_id')))
            ->when($request->filled('venue_id'), fn ($q) => $q->where('venue_id', $request->query('venue_id')))
            ->when($request->filled('from'), fn ($q) => $q->where('starts_at', '>=', $request->query('from')))
            ->when($request->filled('to'), fn ($q) => $q->where('starts_at', '<=', $request->query('to')))
            ->when($request->query('trashed') === 'with', fn ($q) => $q->withTrashed())
            ->when($request->query('trashed') === 'only', fn ($q) => $q->onlyTrashed())
            ->orderByDesc('starts_at');

        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);

        return EventResource::collection($query->paginate($perPage));
    }

    public function store(StoreEventRequest $request)
    {
        // section_prices is not a column of events: keep it out of the mass-assignment payload
        $event = $this->events->create(
            $request->safe()->except(['banner', 'section_prices']),
            $request->file('banner'),
            auth()->id()
        );

        if ($request->has('section_prices')) {
            $event->syncSectionPrices($request->input('section_prices') ?? []);
        }

        return EventResource::make($event->load(['category', 'venue']))
            ->response()->setStatusCode(201);
    }

    public function show(Event $event)
    {
        return EventResource::make($event->load(['category', 'venue']));
    }

    public function update(UpdateEventRequest $request, Event $event)
    {
        $data = $request->safe()->except('section_prices');

        $venueChanged = isset($data['venue_id'])
            && (int) $data['venue_id'] !== (int) $event->venue_id;

        $event = $this->events->update($event, $data);

        if ($request->has('section_prices')) {
            $event->syncSectionPrices($request->input('section_prices') ?? []);
        } elseif ($venueChanged) {
            // Old overrides belong to the previous venue's sections
            $event->syncSectionPrices([]);
        }

        Cache::forget("event_{$event->slug}");

        return EventResource::make($event->load(['category', 'venue']));
    }

    public function destroy(Event $event)
    {
        $event->delete(); // soft delete

        return response()->json(['message' => 'Event deleted']);
    }

    public function restore(Event $event)
    {
        $event->restore();

        return EventResource::make($event->load(['category', 'venue']));
    }

    public function uploadBanner(Request $request, Event $event)
    {
        $request->validate([
            'banner' => [
                'required', 'image',
                'mimes:'.implode(',', config('eventix.banner.mimes')),
                'max:'.config('eventix.banner.max_kb'),
            ],
        ]);

        $event = $this->events->replaceBanner($event, $request->file('banner'));

        return EventResource::make($event->load(['category', 'venue']));
    }

    public function deleteBanner(Event $event)
    {
        $this->events->deleteBanner($event);

        return response()->json(['message' => 'Banner removed']);
    }
}