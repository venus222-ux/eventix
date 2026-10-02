@extends('emails.layout')

@php
    $money = fn (int $cents) => number_format($cents / 100, 2) . ' EUR';
    $title = match ($req->status) {
        'approved' => 'Your refund has been issued',
        'declined' => 'An update on your refund request',
        default => 'We received your refund request',
    };
@endphp

@section('title', $title)
@section('preheader', $title . ' for ' . ($order->event->title ?? 'your order'))

@section('content')
    <h1 style="margin:0 0 8px;font-size:24px;line-height:1.3;color:#111827;">{{ $title }}</h1>
    <p style="margin:0 0 20px;color:#4b5563;">Hi {{ $req->user->name }},</p>

    @if($req->status === 'approved')
        <p style="margin:0 0 16px;">
            We have refunded <strong>{{ $money($order->total_cents) }}</strong> for your order for
            <strong>{{ $order->event->title }}</strong>. The money usually reaches your card within 5&ndash;10 business days,
            depending on your bank.
        </p>
        <p style="margin:0 0 16px;">Your seats have been released and the tickets for this order are no longer valid.</p>
        @if($req->decided_by === 'admin' && $req->decision_note)
            <p style="margin:0 0 16px;padding:12px 16px;background:#f9fafb;border-left:3px solid #4f46e5;color:#374151;">
                {{ $req->decision_note }}
            </p>
        @endif
    @elseif($req->status === 'declined')
        <p style="margin:0 0 16px;">
            After reviewing your request for <strong>{{ $order->event->title }}</strong>, we are not able to refund this order.
        </p>
        @if($req->decision_note)
            <p style="margin:0 0 16px;padding:12px 16px;background:#f9fafb;border-left:3px solid #ef4444;color:#374151;">
                {{ $req->decision_note }}
            </p>
        @endif
        <p style="margin:0 0 16px;">Your tickets remain valid. If you think this is a mistake, just reply to this email.</p>
    @else
        <p style="margin:0 0 16px;">
            We received your refund request for <strong>{{ $order->event->title }}</strong>
            ({{ $money($order->total_cents) }}). A member of our team will review it within {{ $reviewHours }} hours
            and we will email you the outcome.
        </p>
        <p style="margin:0 0 16px;">Until then your tickets stay valid and your seats stay reserved.</p>
    @endif

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
           style="border:1px solid #e5e7eb;border-radius:8px;margin:0 0 24px;font-size:14px;">
        <tr>
            <td style="padding:12px 16px;color:#6b7280;">Reason you gave</td>
            <td style="padding:12px 16px;" align="right">{{ $req->reasonLabel() }}</td>
        </tr>
        <tr>
            <td style="padding:12px 16px;color:#6b7280;border-top:1px solid #f3f4f6;">Order</td>
            <td style="padding:12px 16px;border-top:1px solid #f3f4f6;" align="right">{{ $order->invoice_number ?? $order->id }}</td>
        </tr>
    </table>

    <table role="presentation" cellpadding="0" cellspacing="0">
        <tr>
            <td style="background:#4f46e5;border-radius:8px;">
                <a href="{{ $ordersUrl }}" style="display:inline-block;padding:12px 24px;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;">
                    View my orders
                </a>
            </td>
        </tr>
    </table>
@endsection
