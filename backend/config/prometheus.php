<?php

return [
    'namespace' => 'laravel', // Aceasta va fi prefixul în Grafana (ex: laravel_http_requests_total)

    'redis' => [
        'host' => env('REDIS_HOST', 'redis'), // Folosește numele containerului tău Redis
        'port' => (int) env('REDIS_PORT', 6379),
        'timeout' => 1.0,
        'read_timeout' => '10',
        'persistent_connections' => false,
        'database' => 2, // Folosim baza 2 ca să nu suprascriem datele normale din cache (baza 0)
    ],
];