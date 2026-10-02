<?php

namespace App\Services;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Single place that builds the ticket and invoice PDFs on the 'private' disk.
 * Used by GenerateTicketPdfJob (after payment) AND by the download endpoints,
 * so a download works even if the queue worker is down or the job has not run yet.
 */
class DocumentService
{
    public function tickets(Order $order, bool $force = false): string
    {
        $path = "tickets/order_{$order->id}.pdf";

        if ($force || ! $this->disk()->exists($path)) {
            $order->loadMissing(['items.seat.section', 'event.venue', 'user']);

            $qr = 'data:image/svg+xml;base64,' . base64_encode(
                QrCode::format('svg')->size(200)->generate($order->id)
            );

            $this->disk()->put($path, Pdf::loadView('pdf.ticket', compact('order', 'qr'))->output());
        }

        return $path;
    }

    public function invoice(Order $order, bool $force = false): string
    {
        abort_unless($order->invoice_number, 404, 'No invoice for this order');

        $path = "invoices/{$order->invoice_number}.pdf";

        if ($force || ! $this->disk()->exists($path)) {
            $order->loadMissing(['items.seat.section', 'event', 'user']);

            $this->disk()->put($path, Pdf::loadView('pdf.invoice', compact('order'))->output());
        }

        return $path;
    }

    private function disk(): Filesystem
    {
        return Storage::disk('private');
    }
}