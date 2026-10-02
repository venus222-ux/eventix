<?php

namespace App\Http\Controllers;

use App\Http\Requests\RefundOrderRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Event;
use App\Models\Order;
use App\Services\DocumentService;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\RefundRequestService;
use Illuminate\Support\Facades\Storage;

/**
 * Customer-facing orders (routes behind jwt.auth).
 * The admin version lives in App\Http\Controllers\Admin\OrderController.
 */
class OrderController extends Controller
{
    // POST /api/orders
    public function store(StoreOrderRequest $request, OrderService $orders)
    {
        $event = Event::published()->findOrFail($request->integer('event_id'));

        [$order, $clientSecret] = $orders->checkout(
            auth()->id(),
            $event,
            $request->validated('seat_ids')
        );

        return OrderResource::make($order)
            ->additional(['client_secret' => $clientSecret])
            ->response()
            ->setStatusCode(201);
    }

    // GET /api/orders  (my orders)
    public function index()
    {
        $orders = Order::where('user_id', auth()->id())
            ->with(['event:id,title,slug,starts_at', 'refundRequest']) // event => can_refund, refundRequest => status badge
            ->latest()
            ->paginate(15);

        return OrderResource::collection($orders);
    }

    // GET /api/orders/{order}
    public function show(Order $order, PaymentService $payments)
    {
        $this->authorizeOwner($order);

        $order->load(['user', 'event', 'items.seat.section', 'refundRequest']);

        $resource = OrderResource::make($order);

        // The checkout page reloads this endpoint, so a pending order returns its secret again
        if ($order->status === 'pending' && $order->payment_intent_id) {
            $resource->additional([
                'client_secret' => $payments->retrieve($order->payment_intent_id)->client_secret,
            ]);
        }

        return $resource;
    }

    // POST /api/orders/{order}/cancel
    public function cancel(Order $order, OrderService $orders)
    {
        $this->authorizeOwner($order);

        $orders->cancelPending($order);

        return response()->json(['message' => 'Order cancelled']);
    }

    // POST /api/orders/{order}/refund   { reason, message, accept }
    public function refund(Order $order, RefundOrderRequest $request, RefundRequestService $refunds)
    {
        $this->authorizeOwner($order);

        $refundRequest = $refunds->submit(
            $order,
            auth()->user(),
            $request->validated('reason'),
            $request->validated('message'),
        );

        return OrderResource::make(
            $order->fresh(['user', 'event', 'items.seat.section', 'refundRequest'])
        )->additional([
            // 'approved' = refunded right now, 'pending' = waiting for a human review
            'refund' => ['status' => $refundRequest->status],
        ]);
    }

    // GET /api/orders/{order}/invoice
    public function invoice(Order $order, DocumentService $documents)
    {
        $this->authorizeOwner($order);

        // Built on demand when the queued job has not written the file yet
        $path = $documents->invoice($order);

        return $this->pdf($path, "{$order->invoice_number}.pdf");
    }

    // GET /api/orders/{order}/tickets
    public function tickets(Order $order, DocumentService $documents)
    {
        $this->authorizeOwner($order);

        abort_unless($order->status === 'completed', 404, 'Tickets are only available for paid orders');

        return $this->pdf($documents->tickets($order), 'tickets.pdf');
    }

    private function pdf(string $path, string $filename)
    {
        return Storage::disk('private')->download($path, $filename, ['Content-Type' => 'application/pdf']);
    }

    private function authorizeOwner(Order $order): void
    {
        abort_unless($order->user_id === auth()->id(), 403);
    }
}