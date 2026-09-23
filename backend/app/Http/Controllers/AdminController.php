<?php

namespace App\Http\Controllers;

use App\Models\MongoLog;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Main Dashboard (existing)
     */
    public function dashboard()
    {
        return response()->json([
            'stats' => [
                'logins_today' => MongoLog::where('action', 'login.success')
                    ->whereDate('created_at', now())
                    ->count(),
                'failed_logins_today' => MongoLog::where('action', 'login.failed')
                    ->whereDate('created_at', now())
                    ->count(),
                'active_users' => MongoLog::where('action', 'login.success')
                    ->where('created_at', '>=', now()->subMinutes(15))
                    ->distinct('user_id')
                    ->count(),
            ],
            'recent_activity' => MongoLog::orderBy('created_at', 'desc')
                ->limit(20)
                ->get(),
            'failed_attempts' => MongoLog::where('action', 'login.failed')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
        ]);
    }

    /**
     * New: Traffic Analytics Dashboard
     */

    // ================= Existing Methods =================
    public function users()
    {
        $users = User::with('roles')->get();

        return response()->json($users);
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Cannot delete your own account'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    public function traffic(Request $request)
    {
        $period = $request->query('period', '7d');

        // 1. Core Stats (Standard Eloquent is fine here)
        $query = MongoLog::query();
        match ($period) {
            'today' => $query->whereDate('created_at', now()->toDateString()),
            '7d' => $query->where('created_at', '>=', now()->subDays(7)),
            '30d' => $query->where('created_at', '>=', now()->subDays(30)),
            '12m' => $query->where('created_at', '>=', now()->subMonths(12)),
            default => $query->where('created_at', '>=', now()->subDays(7)),
        };

        $totalVisitors = (clone $query)->count();
        $uniqueVisitors = (clone $query)->distinct('session_id')->count();
        $pageViews = (clone $query)->count();
        $activeUsers = MongoLog::where('created_at', '>=', now()->subMinutes(10))->distinct('user_id')->count();

        // 2. Traffic Trend (Aggregation Pipeline)
        $trend = MongoLog::raw(function ($collection) {
            return $collection->aggregate([
                ['$match' => ['created_at' => ['$gte' => now()->subDays(30)]]],
                ['$group' => [
                    '_id' => ['$dateToString' => ['format' => '%Y-%m-%d', 'date' => '$created_at']],
                    'visitors' => ['$sum' => 1],
                ]],
                ['$sort' => ['_id' => 1]],
            ]);
        })->map(fn ($item) => ['date' => $item['_id'], 'visitors' => $item['visitors']]);

        // 3. Grouped Stats (Aggregation Pipelines)
        $byCountry = $this->aggregateGroup('country');
        $byDevice = $this->aggregateGroup('device');
        $byBrowser = $this->aggregateGroup('browser');

        $recentVisitors = MongoLog::orderBy('created_at', 'desc')->limit(20)->get();

        return response()->json([
            'stats' => ['total_visitors' => $totalVisitors, 'unique_visitors' => $uniqueVisitors, 'page_views' => $pageViews, 'active_users' => $activeUsers],
            'trend' => $trend,
            'by_country' => $byCountry,
            'by_device' => $byDevice,
            'by_browser' => $byBrowser,
            'recent_visitors' => $recentVisitors,
            'online_count' => $activeUsers,
        ]);
    }

    // Helper to keep code clean
    private function aggregateGroup($field)
    {
        return MongoLog::raw(function ($collection) use ($field) {
            return $collection->aggregate([
                ['$match' => [$field => ['$ne' => null]]],
                ['$group' => ['_id' => '$'.$field, 'count' => ['$sum' => 1]]],
                ['$sort' => ['count' => -1]],
            ]);
        })->map(fn ($item) => [$field => $item['_id'], 'count' => $item['count']]);
    }
}
