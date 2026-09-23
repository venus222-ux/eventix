<?php

return [
    // 'public' locally; set EVENT_MEDIA_DISK=s3 to move banners to S3/MinIO without code changes.
    'media_disk' => env('EVENT_MEDIA_DISK', 'public'),

    'banner' => [
        'max_kb' => 2048,
        'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
    ],
];
