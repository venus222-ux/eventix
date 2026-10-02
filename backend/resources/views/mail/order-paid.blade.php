<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <p>Hi {{ $order->user->name }},</p>
<p>Thanks for your purchase of <strong>{{ $order->event->title }}</strong>.
Your tickets and invoice {{ $order->invoice_number }} are attached.</p>
    
</body>
</html>