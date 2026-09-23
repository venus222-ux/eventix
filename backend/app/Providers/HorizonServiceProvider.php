<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // You can use Horizon::auth here if you want to override the default
        // behavior, but parent::boot() handles the gate check automatically.
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null) {
            // Allow access if we are in the local environment
            if (app()->environment('local')) {
                return true;
            }

            // In production, check for specific authorized emails
            return in_array(optional($user)->email, [
                // 'admin@yourdomain.com',
            ]);
        });
    }
}
