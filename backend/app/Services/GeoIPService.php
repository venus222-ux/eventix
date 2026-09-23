<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeoIPService
{
    public static function getLocation(?string $ip): array
    {
        if (empty($ip) || in_array($ip, ['127.0.0.1', '::1', 'localhost'])) {
            return [
                'country' => 'Local',
                'city' => 'Development',
            ];
        }

        try {
            $response = Http::timeout(4)
                ->get("https://ipapi.co/{$ip}/json/");

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'country' => $data['country_name'] ?? 'Unknown',
                    'city' => $data['city'] ?? 'Unknown',
                ];
            }
        } catch (\Exception $e) {
            // Silent fallback
        }

        return [
            'country' => 'Unknown',
            'city' => 'Unknown',
        ];
    }
}
