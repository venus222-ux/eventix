<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Jobs\GenerateTicketPdfJob;
use App\Models\Order;
use App\Services\RefundService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $r)
    {
        $q = Order::query()
            ->with(['user:id,name,email', 'event:id,title,slug,starts_at'])
            ->when($r->filled('status'), fn ($q) => $q->where('status', $r->query('status')))
            ->when($r->filled('q'), fn ($q) => $q->where(fn ($qq) => $qq
                ->where('invoice_number', 'like', '%'.$r->query('q').'%')
                ->orWhereHas('user', fn ($u) => $u->where('email', 'like', '%'.$r->query('q').'%'))))
            ->latest();

        return OrderResource::collection($q->paginate(min(max((int) $r->query('per_page', 15), 1), 100)));
    }

    public function refund(Order $order, RefundService $refunds)
    {
        $refunds->refund($order);   // 409 if the order is not 'completed'

        // a direct refund settles any request still waiting for review
        $order->refundRequests()->where('status', 'pending')->update([
            'status' => 'approved',
            'decided_by' => 'admin',
            'decided_by_user_id' => auth()->id(),
            'decision_note' => 'Refunded directly by an admin',
            'decided_at' => now(),
        ]);

        return OrderResource::make($order->fresh(['user', 'event', 'items.seat.section']));
    }

    public function resend(Order $order)
    {
        abort_unless($order->status === 'completed', 409);
        GenerateTicketPdfJob::dispatch($order->id);

        return response()->json(['message' => 'Email queued']);
    }
}