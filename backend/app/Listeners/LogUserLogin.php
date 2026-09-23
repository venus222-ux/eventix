<?php

namespace App\Listeners;

use App\Events\Auth\UserLoggedIn;
use Illuminate\Support\Facades\Log;

class LogUserLogin
{
    /**
     * Handle the event.
     */
    public function handle(UserLoggedIn $event): void
    {
        Log::info('User logged in', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'time' => now(),
            'ip' => request()->ip(),
        ]);
    }
}
