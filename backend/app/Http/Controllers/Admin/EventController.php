<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEventRequest;
use App\Http\Requests\Admin\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Services\EventService;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function __construct(private EventService $events) {}

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
        $event = $this->events->create(
            $request->safe()->except('banner'),
            $request->file('banner'),
            auth()->id()
        );

        return EventResource::make($event->load(['category', 'venue']))
            ->response()->setStatusCode(201);
    }

    public function show(Event $event)
    {
        return EventResource::make($event->load(['category', 'venue']));
    }

    public function update(UpdateEventRequest $request, Event $event)
    {
        $event = $this->events->update($event, $request->validated());

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
