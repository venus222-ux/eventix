<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    // ================= USER =================

    public function createUser(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
        ]);

        $user->assignRole('user');

        return $user;
    }

    // ================= LOGIN =================

    public function attemptLogin(array $credentials): ?User
    {
        if (! auth('api')->attempt($credentials)) {
            return null;
        }

        return auth('api')->user();
    }

    public function markOnline(User $user): void
    {
        Cache::put("user-online-{$user->id}", true, now()->addMinutes(5));
    }

    // ================= JWT TOKENS =================

    public function makeAccessToken(User $user): string
    {
        return JWTAuth::claims([
            'type' => 'access',
            'role' => $user->getRoleNames()->first(),
        ])->fromUser($user);
    }

    public function makeRefreshToken(User $user): string
    {
        return JWTAuth::claims([
            'type' => 'refresh',
        ])->fromUser($user);
    }

    // ================= REFRESH TOKENS =================

    public function storeRefreshToken(User $user, string $token): void
    {
        DB::table('refresh_tokens')->insert([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays(14),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function revokeUserTokens(User $user): void
    {
        DB::table('refresh_tokens')
            ->where('user_id', $user->id)
            ->update(['revoked' => true]);
    }

    public function rotateRefreshToken(User $user, string $oldToken): string
    {
        DB::table('refresh_tokens')
            ->where('token_hash', hash('sha256', $oldToken))
            ->update(['revoked' => true]);

        $newToken = $this->makeRefreshToken($user);
        $this->storeRefreshToken($user, $newToken);

        return $newToken;
    }
}
