<?php

namespace App\Listeners;

use App\Events\Auth\PasswordResetRequested;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendResetPasswordNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public $tries = 3;           // Add this

    public $timeout = 60;

    public function handle(PasswordResetRequested $event): void
    {
        // Idempotency check
        $cacheKey = 'password_reset_notification_sent:'.$event->user->id.':'.hash('xxh3', $event->token);

        if (cache()->has($cacheKey)) {
            $this->delete(); // Remove duplicate job

            return;
        }

        cache()->put($cacheKey, true, now()->addMinutes(10));

        $event->user->notify(new ResetPasswordNotification($event->token));
    }
}
