<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class ReservationService
{
    /**
     * Releases ONLY 'locked' keys (a hold). 'sold' keys are left alone.
     *
     * The previous script did GET on the raw seat id (e.g. "5") instead of
     * "event:{eventId}:seat:{seatId}", compared it to the event id, and only looked at
     * KEYS[1]. It never deleted anything, so cancelled / expired / failed holds stayed
     * 'locked' for the full TTL and the next checkout returned 409
     * "Someone else is holding one of these seats."
     */
    private string $releaseLuaScript = <<<'LUA'
        local deleted = 0
        for i, seat_id in ipairs(KEYS) do
            local key = 'event:' .. ARGV[1] .. ':seat:' .. seat_id
            if redis.call('GET', key) == 'locked' then
                redis.call('DEL', key)
                deleted = deleted + 1
            end
        end
        return deleted
    LUA;

    /** Deletes the seat keys whatever their state ('locked' or 'sold'). Used after a refund. */
    private string $clearLuaScript = <<<'LUA'
        local deleted = 0
        for i, seat_id in ipairs(KEYS) do
            deleted = deleted + redis.call('DEL', 'event:' .. ARGV[1] .. ':seat:' .. seat_id)
        end
        return deleted
    LUA;

    private string $reserveLuaScript = <<<'LUA'
        local event_id = ARGV[1]
        local ttl = ARGV[2]

        for i, seat_id in ipairs(KEYS) do
            local status = redis.call('GET', 'event:' .. event_id .. ':seat:' .. seat_id)
            if status == 'sold' or status == 'locked' then
                return 0
            end
        end

        for i, seat_id in ipairs(KEYS) do
            redis.call('SET', 'event:' .. event_id .. ':seat:' .. seat_id, 'locked', 'EX', ttl)
        end

        return 1
    LUA;

    private string $lockedLuaScript = <<<'LUA'
        local locked = {}
        for i, seat_id in ipairs(KEYS) do
            if redis.call('GET', 'event:' .. ARGV[1] .. ':seat:' .. seat_id) == 'locked' then
                table.insert(locked, seat_id)
            end
        end
        return locked
    LUA;

    public function lockedSeatIds(int $eventId, array $seatIds): array
    {
        if (! $seatIds) {
            return [];
        }

        return array_map('intval', Redis::eval(
            $this->lockedLuaScript,
            count($seatIds),
            ...array_merge($seatIds, [$eventId])
        ));
    }

    /** Atomically locks the seats in Redis for $ttlSeconds. */
    public function reserveSeats(int $eventId, array $seatIds, int $ttlSeconds = 600): bool
    {
        $result = Redis::eval(
            $this->reserveLuaScript,
            count($seatIds),
            ...array_merge($seatIds, [$eventId, $ttlSeconds])
        );

        return (bool) $result;
    }

    /** Releases the holds (cancel / expiry / failed checkout). */
    public function releaseSeats(int $eventId, array $seatIds): bool
    {
        if (! $seatIds) {
            return false;
        }

        return (bool) Redis::eval(
            $this->releaseLuaScript,
            count($seatIds),
            ...array_merge($seatIds, [$eventId])
        );
    }

    /** Frees seats completely, including 'sold' keys. Call it from markRefunded(). */
    public function clearSeats(int $eventId, array $seatIds): bool
    {
        if (! $seatIds) {
            return false;
        }

        return (bool) Redis::eval(
            $this->clearLuaScript,
            count($seatIds),
            ...array_merge($seatIds, [$eventId])
        );
    }
}