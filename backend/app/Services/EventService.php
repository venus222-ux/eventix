<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EventService
{
    public function create(array $data, ?UploadedFile $banner, ?int $userId): Event
    {
        $event = DB::transaction(function () use ($data, $userId) {
            $data['slug'] = $this->uniqueSlug($data['title']);
            $data['created_by'] = $userId;

            return Event::create($data);
        });

        if ($banner) {
            $this->replaceBanner($event, $banner);
        }

        return $event;
    }

    public function update(Event $event, array $data): Event
    {
        // The slug is intentionally NOT regenerated: stable URLs matter more than a title change.
        $event->update($data);

        return $event;
    }

    public function replaceBanner(Event $event, UploadedFile $file): Event
    {
        $diskName = config('eventix.media_disk');
        $old = $event->banner_path;

        // extension() is guessed from the real MIME type, not the client-provided name.
        $path = $file->storeAs(
            "events/{$event->id}",
            Str::uuid().'.'.$file->extension(),
            $diskName
        );

        $event->update(['banner_path' => $path]);

        if ($old) {
            Storage::disk($diskName)->delete($old);
        }

        return $event;
    }

    public function deleteBanner(Event $event): Event
    {
        if ($event->banner_path) {
            Storage::disk(config('eventix.media_disk'))->delete($event->banner_path);
            $event->update(['banner_path' => null]);
        }

        return $event;
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'event';
        $slug = $base;
        $i = 2;

        while (Event::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
