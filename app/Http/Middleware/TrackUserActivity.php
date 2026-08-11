<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class TrackUserActivity
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $user = $request->user();

        if (!$user || !Schema::hasColumn('users', 'last_seen_at')) {
            return $response;
        }

        $now = now();
        $cacheKey = 'user_last_seen_update_' . $user->id;

        if (!Cache::has($cacheKey)) {
            $user->forceFill([
                'last_seen_at' => $now,
            ])->save();

            Cache::put($cacheKey, true, $now->copy()->addMinutes(2));
        }

        if ($this->activityTableExists()) {
            $this->trackActiveTime($user->id, $now);
        }

        return $response;
    }

    private function activityTableExists(): bool
    {
        return Cache::remember('user_activity_summaries_table_exists', 600, function () {
            return Schema::hasTable('user_activity_summaries');
        });
    }

    private function trackActiveTime(int $userId, Carbon $now): void
    {
        $lastPingKey = 'user_activity_last_ping_' . $userId;
        $bufferKey = 'user_activity_buffer_' . $userId . '_' . $now->toDateString();
        $persistKey = 'user_activity_persist_' . $userId;
        $lastPing = Cache::get($lastPingKey);

        Cache::put($lastPingKey, $now->toIso8601String(), $now->copy()->addHours(8));

        if (!$lastPing) {
            return;
        }

        try {
            $lastPingAt = Carbon::parse($lastPing);
        } catch (\Throwable $e) {
            return;
        }

        $diffSeconds = $lastPingAt->diffInSeconds($now);

        if ($diffSeconds < 30 || $diffSeconds > 600) {
            return;
        }

        Cache::add($bufferKey, 0, $now->copy()->addDays(8));
        Cache::increment($bufferKey, min($diffSeconds, 300));

        if (Cache::has($persistKey)) {
            return;
        }

        $pendingSeconds = (int) Cache::get($bufferKey, 0);
        if ($pendingSeconds <= 0) {
            return;
        }

        Cache::forget($bufferKey);
        Cache::put($persistKey, true, $now->copy()->addMinutes(2));

        DB::statement(
            'INSERT INTO user_activity_summaries (user_id, activity_date, active_seconds, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE active_seconds = active_seconds + VALUES(active_seconds), updated_at = VALUES(updated_at)',
            [$userId, $now->toDateString(), $pendingSeconds, $now, $now]
        );
    }
}
