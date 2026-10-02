<?php

return [
    // 'public' locally; set EVENT_MEDIA_DISK=s3 to move banners to S3/MinIO without code changes.
    'media_disk' => env('EVENT_MEDIA_DISK', 'public'),

    'banner' => [
        'max_kb' => 2048,
        'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
    ],
    // config/eventix.php
'vat_rate' => (float) env('EVENTIX_VAT_RATE', 19),
'refund_window_hours' => (int) env('EVENTIX_REFUND_WINDOW_HOURS', 48),
'refund_auto_approve_max_cents'   => (int) env('REFUND_AUTO_APPROVE_MAX_CENTS', 50000),
'refund_auto_approve_max_per_30d' => (int) env('REFUND_AUTO_APPROVE_MAX_PER_30D', 3),
'refund_review_hours'             => 24,
];
