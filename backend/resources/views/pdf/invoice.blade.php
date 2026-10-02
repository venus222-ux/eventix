<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $order->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 13px; color: #111; }
        .header { margin-bottom: 20px; }
        .seller-info { float: left; width: 50%; }
        .invoice-info { float: right; width: 45%; text-align: right; }
        .clear { clear: both; }
        .billing-box { background: #f9f9f9; border: 1px solid #eee; padding: 12px; margin: 20px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f3f4f6; }
        .text-right { text-align: right; }
        .footer { margin-top: 40px; font-size: 11px; color: #666; text-align: center; }
    </style>
</head>
<body>
@php
    $u = $order->user;

    // Frozen at payment time; falls back to the live profile for old orders
    $b = $order->billing_address ?? [
        'name' => $u->name,
        'email' => $u->email,
        'country' => $u->billing_country,
        'city' => $u->billing_city,
        'postal_code' => $u->billing_postal_code,
        'street' => $u->billing_street,
    ];

    $countryName = ! empty($b['country'])
        ? (class_exists(\Locale::class) ? \Locale::getDisplayRegion('-' . $b['country'], 'ro') : $b['country'])
        : null;

    $addressLine = collect([$b['street'] ?? null, $b['postal_code'] ?? null, $b['city'] ?? null, $countryName])
        ->filter()
        ->implode(', ');

    $vatRate = rtrim(rtrim(number_format((float) $order->vat_rate, 2), '0'), '.');
@endphp

    <div class="header">
        <div class="seller-info">
            <h2>Your Event Store SRL</h2>
            <p>
                Str. Exemplu Nr. 10, București, România<br>
                VAT ID: RO12345678<br>
                Email: support@eventstore.com
            </p>
        </div>

        <div class="invoice-info">
            <h2>INVOICE</h2>
            <p>
                <strong>Invoice Number:</strong> {{ $order->invoice_number }}<br>
                <strong>Order ID:</strong> {{ $order->id }}<br>
                <strong>Issue Date:</strong> {{ ($order->paid_at ?? $order->created_at)->format('Y-m-d') }}
            </p>
        </div>

        <div class="clear"></div>
    </div>

    <div class="billing-box">
        <strong>Bill To:</strong><br>
        {{ $b['name'] }} ({{ $b['email'] }})<br>
        {{ $addressLine ?: 'Billing address not provided' }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Seat Details</th>
                <th class="text-right">Price</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>{{ $order->event->title }}</td>
                    <td>{{ $item->seat->section->name }} - {{ $item->seat->label() }}</td>
                    <td class="text-right">{{ number_format($item->unit_price_cents / 100, 2) }} EUR</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 15px; text-align: right;">
        <p>Subtotal: {{ number_format($order->subtotal_cents / 100, 2) }} EUR</p>
        <p>VAT ({{ $vatRate }}%): {{ number_format($order->vat_cents / 100, 2) }} EUR</p>
        <h3>Grand Total: {{ number_format($order->total_cents / 100, 2) }} EUR</h3>
    </div>

    <div class="footer">
        <p>Payment Method: Credit Card (Stripe)</p>
        <p>Digital Delivery: Tickets delivered via email and user dashboard.</p>
        <p>Thank you for your business!</p>
    </div>
</body>
</html>