<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogger;
use App\Services\GeoIPService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        // Prevent logging for the traffic API itself
        if ($request->is('api/admin/traffic')) {
            return $next($request);
        }

        $response = $next($request);

        // 1. Generate a unique key for this specific page/request
        // This combines the path and query string to identify the specific "view"
        $pageKey = 'visited_'.md5($request->fullUrl());

        // 2. Check if the user has already "visited" this page in the current session
        if (! $request->session()->has($pageKey)) {

            // 3. If not visited, log the activity
            $user = auth()->user();
            $ip = $request->ip();
            $location = GeoIPService::getLocation($ip);
            $sessionId = $request->session()->getId();

            ActivityLogger::log([
                'user_id' => $user?->id,
                'email' => $user?->email ?? $request->input('email'),
                'action' => $request->method().' '.$request->path(),
                'status' => $response->getStatusCode() < 400 ? 'success' : 'error',
                'ip_address' => $ip,
                'user_agent' => $request->userAgent(),
                'device' => $this->getDeviceType($request),
                'browser' => $this->getBrowser($request),
                'country' => $location['country'],
                'city' => $location['city'],
                'page' => $request->path(),
                'session_id' => $sessionId,
            ]);

            // 4. Mark this page as visited in the session
            $request->session()->put($pageKey, true);
        }

        return $response;
    }

    private function getDeviceType(Request $request): string
    {
        $agent = $request->userAgent() ?? '';
        if (preg_match('/(android|iphone|ipod|mobile|blackberry)/i', $agent)) {
            return 'Mobile';
        }
        if (preg_match('/(tablet|ipad)/i', $agent)) {
            return 'Tablet';
        }

        return 'Desktop';
    }

    private function getBrowser(Request $request): string
    {
        $agent = $request->userAgent() ?? '';
        if (str_contains($agent, 'Chrome') && ! str_contains($agent, 'Edg')) {
            return 'Chrome';
        }
        if (str_contains($agent, 'Firefox')) {
            return 'Firefox';
        }
        if (str_contains($agent, 'Safari') && ! str_contains($agent, 'Chrome')) {
            return 'Safari';
        }
        if (str_contains($agent, 'Edg')) {
            return 'Edge';
        }

        return 'Other';
    }
}
