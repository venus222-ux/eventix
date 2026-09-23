<?php

namespace App\Http\Controllers;

use App\Events\Auth\PasswordResetRequested;
use App\Events\Auth\UserLoggedIn;
use App\Events\Auth\UserRegistered;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\AuthService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    // ================= COOKIE HELPERS =================

    protected function refreshCookie(string $token)
    {
        return cookie(
            'refresh_token',
            $token,
            60 * 24 * 14,
            '/',
            null,
            false,
            true,
            false,
            'lax'
        );
    }

    protected function clearRefreshCookie()
    {
        return cookie('refresh_token', '', -1);
    }

    // ================= REGISTER =================

    public function register(RegisterRequest $request, AuthService $authService)
    {
        $user = $authService->createUser($request->validated());

        event(new UserRegistered($user));

        $accessToken = $authService->makeAccessToken($user);
        $refreshToken = $authService->makeRefreshToken($user);

        $authService->storeRefreshToken($user, $refreshToken);

        return (new UserResource($user))
            ->additional([
                'token' => $accessToken,
                'role' => $user->getRoleNames()->first(),
            ])
            ->response()
            ->cookie($this->refreshCookie($refreshToken));
    }

    // ================= LOGIN =================

    public function login(LoginRequest $request, AuthService $authService)
    {
        $credentials = $request->validated();

        $user = $authService->attemptLogin($credentials);

        if (! $user) {
            // log failed login attempt
            ActivityLogger::log([
                'user_id' => null,
                'email' => $credentials['email'] ?? null,
                'action' => 'login.failed',
                'status' => 'error',
            ]);

            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // log successful login
        ActivityLogger::log([
            'user_id' => $user->id,
            'email' => $user->email,
            'action' => 'login.success',
            'status' => 'success',
        ]);

        event(new UserLoggedIn($user));
        $authService->markOnline($user);

        $accessToken = $authService->makeAccessToken($user);
        $refreshToken = $authService->makeRefreshToken($user);

        $authService->storeRefreshToken($user, $refreshToken);

        return (new UserResource($user))
            ->additional([
                'token' => $accessToken,
                'role' => $user->getRoleNames()->first(),
            ])
            ->response()
            ->cookie($this->refreshCookie($refreshToken));
    }
    // ================= REFRESH =================

    public function refresh(Request $request, AuthService $authService)
    {
        $refreshToken = $request->cookie('refresh_token');

        if (! $refreshToken) {
            return response()->json(['message' => 'No refresh token'], 401);
        }

        try {
            $payload = JWTAuth::setToken($refreshToken)->getPayload();

            if ($payload->get('type') !== 'refresh') {
                return response()->json(['message' => 'Invalid token type'], 401);
            }

            $user = User::find($payload->get('sub'));

            if (! $user) {
                return response()->json(['message' => 'User not found'], 401);
            }

            $stored = DB::table('refresh_tokens')
                ->where('token_hash', hash('sha256', $refreshToken))
                ->first();

            if (! $stored || $stored->revoked) {
                $authService->revokeUserTokens($user);

                return response()->json(['message' => 'Token reuse detected'], 401);
            }
            if (now()->greaterThan($stored->expires_at)) {
                return response()->json(['message' => 'Token expired'], 401);
            }

            DB::transaction(function () use ($stored) {
                DB::table('refresh_tokens')
                    ->where('id', $stored->id)
                    ->update(['revoked' => true]);
            });

            $newRefreshToken = $authService->makeRefreshToken($user);
            $authService->storeRefreshToken($user, $newRefreshToken);

            $accessToken = $authService->makeAccessToken($user);

            return (new UserResource($user))
                ->additional([
                    'token' => $accessToken,
                    'role' => $user->getRoleNames()->first(),
                ])
                ->response()
                ->cookie($this->refreshCookie($newRefreshToken));

        } catch (\Throwable $e) {
            return response()->json(['message' => 'Invalid refresh token'], 401);
        }
    }

    // ================= LOGOUT =================

    public function logout(AuthService $authService)
    {
        $user = auth()->user();

        if ($user) {
            $authService->revokeUserTokens($user);
        }

        auth()->logout();

        return response()
            ->json(['message' => 'Logged out'])
            ->cookie($this->clearRefreshCookie());
    }

    // ================= PROFILE =================

    public function me()
    {
        try {
            return new UserResource(
                JWTAuth::parseToken()->authenticate()
            );
        } catch (\Throwable) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
    }

    public function profile()
    {
        $user = JWTAuth::parseToken()->authenticate();

        return new UserResource($user);
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $user->update([
            'email' => $request->email,
            'password' => $request->password
                ? bcrypt($request->password)
                : $user->password,
        ]);

        return response()->json(['message' => 'Profile updated']);
    }

    public function destroyProfile()
    {
        $user = JWTAuth::parseToken()->authenticate();
        $user->delete();

        return response()->json(['message' => 'Account deleted']);
    }

    // ================= PASSWORD RESET =================

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $token = Str::random(64);

        DB::table('password_resets')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => hash('sha256', $token),
                'created_at' => now(),
            ]
        );

        $user = User::where('email', $request->email)->first();

        event(new PasswordResetRequested($user, $token));

        return response()->json(['message' => 'Reset link sent']);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $data = $request->validated();

        $hashedToken = hash('sha256', $data['token']);

        $reset = DB::table('password_resets')
            ->where('email', $data['email'])
            ->where('token', $hashedToken)
            ->first();

        if (! $reset) {
            return response()->json(['message' => 'Invalid token'], 400);
        }

        if (Carbon::parse($reset->created_at)->addMinutes(60)->isPast()) {
            return response()->json(['message' => 'Token expired'], 400);
        }

        $user = User::where('email', $data['email'])->first();

        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        DB::table('password_resets')
            ->where('email', $data['email'])
            ->delete();

        return response()->json(['message' => 'Password reset success']);
    }
}
