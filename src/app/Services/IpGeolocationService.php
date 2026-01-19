<?php


namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class IpGeolocationService
{
    public function getCountry(string $ip): ?string
    {
        // Логика из AppServiceProvider
        if (in_array($ip, ['127.0.0.1', '::1', 'localhost'])) {
            return 'local';
        }

        return Cache::remember("ip_country:{$ip}", now()->addDays(7), function () use ($ip) {
            try {
                $response = Http::timeout(2)
                    ->get("http://ip-api.com/json/{$ip}?fields=country,countryCode,status");

                if ($response->successful()) {
                    $data = $response->json();
                    if ($data['status'] === 'success') {
                        return $data['country'] ?? $data['countryCode'] ?? null;
                    }
                }

                return null;
            } catch (\Throwable $e) {
                Log::error('Ошибка выброшена в сервисе IpGeolocationService,
                      в методе getCountry: ' . $e->getMessage());

                return null;
            }
        });
    }
}
