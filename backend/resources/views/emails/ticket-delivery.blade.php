@extends('emails.layout')

@section('title', 'Your tickets')
@section('preheader', 'Your tickets for ' . $order->event->title . ' are attached.')

@section('content')
    @php
        $event = $order->event;
        $money = fn (int $cents) => number_format($cents / 100, 2) . ' EUR';
        $vatRate = rtrim(rtrim(number_format((float) $order->vat_rate, 2), '0'), '.');
    @endphp

    <h1 style="margin:0 0 8px;font-size:24px;line-height:1.3;color:#111827;">You're going to {{ $event->title }}</h1>
    <p style="margin:0 0 20px;color:#4b5563;">Hi {{ $order->user->name }}, thank you for your purchase. Your payment was received.</p>

    {{-- Event card --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
           style="border:1px solid #e5e7eb;border-radius:8px;margin:0 0 24px;">
        <tr>
            <td style="padding:16px 20px;">
                <div style="font-size:12px;text-transform:uppercase;letter-spacing:.6px;color:#6b7280;">When</div>
                <div style="font-weight:600;margin-bottom:12px;">{{ $event->starts_at->format('l, j F Y \a\t H:i') }}</div>

                @if($event->venue)
                    <div style="font-size:12px;text-transform:uppercase;letter-spacing:.6px;color:#6b7280;">Where</div>
                    <div style="font-weight:600;">
                        {{ $event->venue->name }}<br>
                        <span style="font-weight:400;color:#4b5563;">
                            {{ collect([$event->venue->address, $event->venue->city])->filter()->implode(', ') }}
                        </span>
                    </div>
                @endif
            </td>
        </tr>
    </table>

    {{-- Seats --}}
    <h2 style="margin:0 0 8px;font-size:16px;">Your seats</h2>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;font-size:14px;">
        @foreach($order->items as $item)
            <tr>
                <td style="padding:8px 0;border-bottom:1px solid #f3f4f6;">
                    {{ $item->seat->section->name }} &middot; Seat {{ $item->seat->label() }}
                </td>
                <td align="right" style="padding:8px 0;border-bottom:1px solid #f3f4f6;">{{ $money($item->unit_price_cents) }}</td>
            </tr>
        @endforeach
        <tr>
            <td style="padding:8px 0 0;color:#6b7280;">Subtotal</td>
            <td align="right" style="padding:8px 0 0;color:#6b7280;">{{ $money($order->subtotal_cents) }}</td>
        </tr>
        <tr>
            <td style="padding:2px 0;color:#6b7280;">VAT ({{ $vatRate }}%)</td>
            <td align="right" style="padding:2px 0;color:#6b7280;">{{ $money($order->vat_cents) }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;font-weight:700;">Total paid</td>
            <td align="right" style="padding:8px 0;font-weight:700;">{{ $money($order->total_cents) }}</td>
        </tr>
    </table>

    {{-- CTA --}}
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
        <tr>
            <td style="background:#4f46e5;border-radius:8px;">
                <a href="{{ $ordersUrl }}"
                   style="display:inline-block;padding:12px 24px;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;">
                    View my orders
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 8px;color:#4b5563;font-size:14px;">
        <strong>Attached to this email:</strong> your tickets (PDF) and invoice {{ $order->invoice_number }}.
        You can download both again at any time from <em>My orders</em>.
    </p>
    <p style="margin:0;color:#6b7280;font-size:13px;">Order reference: {{ $order->id }}</p>
@endsection
