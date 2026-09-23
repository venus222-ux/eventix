<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $token = JWTAuth::parseToken();

            $user = $token->authenticate();

            if (! $user) {
                return response()->json(['message' => 'User not found'], 401);
            }

            $payload = $token->getPayload();

            if ($payload->get('type') !== 'access') {
                return response()->json(['message' => 'Invalid access token'], 401);
            }

            // ✅ IMPORTANT: set user globally
            auth()->setUser($user);

        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        return $next($request);
    }
}
