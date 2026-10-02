<?php

namespace App\Jobs;

use App\Mail\TicketDeliveryMail;
use App\Models\Order;
use App\Services\DocumentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class GenerateTicketPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public string $orderId) {}

    public function handle(DocumentService $documents): void
    {
        $order = Order::with(['items.seat.section', 'event.venue', 'user'])->findOrFail($this->orderId);

        // force: regenerate, so the admin "Resend" button also refreshes the files
        $ticketPath = $documents->tickets($order, force: true);
        $invoicePath = $documents->invoice($order, force: true);

        Mail::to($order->user->email)->send(new TicketDeliveryMail($order, $ticketPath, $invoicePath));
    }
}