<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketDeliveryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public string $ticketPath,
        public string $invoicePath,
    ) {}

    public function envelope(): Envelope
    {
        $this->order->loadMissing('event');

        return new Envelope(subject: "Your tickets for {$this->order->event->title}");
    }

    public function content(): Content
    {
        $this->order->loadMissing(['items.seat.section', 'event.venue', 'user']);

        return new Content(
            view: 'emails.ticket-delivery',
            with: [
                'order' => $this->order,
                'ordersUrl' => rtrim(config('app.frontend_url', config('app.url')), '/') . '/dashboard',
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk('private', $this->ticketPath)
                ->as('Tickets.pdf')
                ->withMime('application/pdf'),
            Attachment::fromStorageDisk('private', $this->invoicePath)
                ->as("Invoice {$this->order->invoice_number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}