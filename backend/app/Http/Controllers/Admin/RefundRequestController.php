<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RefundRequest;
use App\Services\RefundRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RefundRequestController extends Controller
{
    // GET /api/admin/refund-requests?status=pending
    public function index(Request $request): JsonResponse
    {
        $page = RefundRequest::query()
            ->with(['user:id,name,email', 'order:id,total_cents,invoice_number,event_id', 'order.event:id,title,starts_at'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->latest()
            ->paginate(min(max((int) $request->query('per_page', 15), 1), 100));

        return response()->json([
            'data' => $page->getCollection()->map(fn (RefundRequest $r) => [
                'id' => $r->id,
                'status' => $r->status,
                'reason' => $r->reason,
                'reason_label' => $r->reasonLabel(),
                'message' => $r->message,
                'customer' => ['name' => $r->user?->name, 'email' => $r->user?->email],
                'order' => [
                    'id' => $r->order_id,
                    'invoice_number' => $r->order?->invoice_number,
                    'total_cents' => $r->order?->total_cents,
                    'event_title' => $r->order?->event?->title,
                    'event_starts_at' => $r->order?->event?->starts_at?->toIso8601String(),
                ],
                'snapshot' => $r->snapshot,
                'decided_by' => $r->decided_by,
                'decision_note' => $r->decision_note,
                'created_at' => $r->created_at?->toIso8601String(),
            ])->values(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
                'per_page' => $page->perPage(),
            ],
        ]);
    }

    // POST /api/admin/refund-requests/{refundRequest}/approve   { note? }
    public function approve(Request $request, RefundRequest $refundRequest, RefundRequestService $service): JsonResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:500']]);

        $service->approve($refundRequest, 'admin', auth()->user(), $data['note'] ?? null);

        return response()->json(['message' => 'Refund approved and issued']);
    }

    // POST /api/admin/refund-requests/{refundRequest}/decline   { note }
    public function decline(Request $request, RefundRequest $refundRequest, RefundRequestService $service): JsonResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'min:5', 'max:500']]);

        $service->decline($refundRequest, auth()->user(), $data['note']);

        return response()->json(['message' => 'Refund request declined']);
    }
}