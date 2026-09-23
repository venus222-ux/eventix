<?php

namespace App\Services;

use App\Models\MongoLog;

class ActivityLogger
{
    public static function log(array $data): void
    {
        MongoLog::create([
            'user_id' => $data['user_id'] ?? null,
            'email' => $data['email'] ?? null,
            'action' => $data['action'],
            'status' => $data['status'],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'device' => self::getDevice(),
            'browser' => self::getBrowser(),
            'failure_reason' => $data['failure_reason'] ?? null,
            'created_at' => now(),
        ]);
    }

    protected static function getDevice()
    {
        $agent = request()->userAgent();

        if (str_contains($agent, 'Mobile')) {
            return 'mobile';
        }
        if (str_contains($agent, 'Tablet')) {
            return 'tablet';
        }

        return 'desktop';
    }

    protected static function getBrowser()
    {
        $agent = request()->userAgent();

        if (str_contains($agent, 'Chrome')) {
            return 'Chrome';
        }
        if (str_contains($agent, 'Firefox')) {
            return 'Firefox';
        }
        if (str_contains($agent, 'Safari')) {
            return 'Safari';
        }

        return 'Unknown';
    }
}
