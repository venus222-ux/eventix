<?php

namespace App\Providers;

use App\Events\Auth\PasswordResetRequested;
use App\Events\Auth\UserLoggedIn;
use App\Events\Auth\UserRegistered;
use App\Listeners\LogUserLogin;
use App\Listeners\SendResetPasswordNotification;
use App\Listeners\SendWelcomeEmail;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        UserRegistered::class => [SendWelcomeEmail::class],
        UserLoggedIn::class => [LogUserLogin::class],
        // PasswordResetRequested is handled manually below
    ];

    public function boot(): void
    {
        parent::boot();

        // === CRITICAL: Disable discovery completely ===
        static::disableEventDiscovery();

        // Remove any existing registrations for this event
        Event::forget(PasswordResetRequested::class);

        // Register exactly once
        Event::listen(
            PasswordResetRequested::class,
            SendResetPasswordNotification::class
        );
    }

    /**
     * Explicitly disable discovery
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
