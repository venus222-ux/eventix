<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function sales(): JsonResponse
    {
        $paid = Order::where('status', 'completed');

        return response()->json([
            'total_revenue_cents' => (clone $paid)->sum('total_cents'),
            'refunded_cents' => Order::where('status', 'refunded')->sum('total_cents'),
            'orders' => (clone $paid)->count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'tickets_sold' => OrderItem::whereHas('order', fn ($q) => $q->where('status', 'completed'))->count(),
            'by_day' => (clone $paid)->where('paid_at', '>=', now()->subDays(30))
                ->selectRaw('DATE(paid_at) as day, SUM(total_cents) as revenue, COUNT(*) as orders')
                ->groupBy('day')->orderBy('day')->get(),
            'top_events' => (clone $paid)->join('events', 'events.id', '=', 'orders.event_id')
                ->selectRaw('events.id, events.title, SUM(orders.total_cents) as revenue, COUNT(*) as orders')
                ->groupBy('events.id', 'events.title')->orderByDesc('revenue')->limit(5)->get(),
        ]);
    }
}
