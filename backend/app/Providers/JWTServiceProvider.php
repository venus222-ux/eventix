<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Tymon\JWTAuth\JWTGuard;

class JWTServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Auth::extend('jwt', function ($app, $name, array $config) {
            // Correct argument order: JWT, UserProvider, Request
            return new JWTGuard(
                $app['tymon.jwt'], // JWT service
                Auth::createUserProvider($config['provider']),
                $app['request']
            );
        });
    }
}
