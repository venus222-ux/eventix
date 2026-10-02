<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 13px; color: #111; }
  .ticket { border: 2px solid #4f46e5; border-radius: 8px; padding: 20px; margin-bottom: 18px; }
  h1 { margin: 0 0 4px; font-size: 20px; }
  .muted { color: #666; }
  .seat { font-size: 18px; font-weight: bold; margin-top: 10px; }
</style>
</head>
<body>
  @foreach($order->items as $item)
    <div class="ticket">
      <h1>{{ $order->event->title }}</h1>
      <div class="muted">
        {{ $order->event->starts_at->format('D, d M Y H:i') }}
        @if($order->event->venue) · {{ $order->event->venue->name }}, {{ $order->event->venue->city }} @endif
      </div>
      <div class="seat">{{ $item->seat->section->name }} — Seat {{ $item->seat->label() }}</div>
      <div class="muted">Order {{ $order->id }}</div>
      <img src="{{ $qr }}" width="110" height="110" style="margin-top:10px">
    </div>
  @endforeach
</body>
</html>