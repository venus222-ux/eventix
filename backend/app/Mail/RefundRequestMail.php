<?php

namespace App\Mail;

use App\Models\RefundRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RefundRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public RefundRequest $refundRequest) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: match ($this->refundRequest->status) {
            'approved' => 'Your refund has been issued',
            'declined' => 'An update on your refund request',
            default => 'We received your refund request',
        });
    }

    public function content(): Content
    {
        $this->refundRequest->loadMissing(['order.event', 'user']);

        return new Content(
            view: 'emails.refund-update',
            with: [
                'req' => $this->refundRequest,
                'order' => $this->refundRequest->order,
                'ordersUrl' => rtrim(config('app.frontend_url', config('app.url')), '/') . '/dashboard',
                'reviewHours' => (int) config('eventix.refund_review_hours', 24),
            ],
        );
    }
}