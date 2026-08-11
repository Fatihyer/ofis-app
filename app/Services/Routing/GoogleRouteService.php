<?php

namespace App\Services\Routing;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleRouteService
{
    public function compute(string $originAddress, string $destinationAddress, array $waypoints = []): array
    {
        $apiKey = config('services.google_maps.routes_api_key');
        $url = config('services.google_maps.routes_url');

        if (!$apiKey) {
            throw new RuntimeException('GOOGLE_MAPS_API_KEY tanımlı değil.');
        }

        $intermediates = [];

        foreach ($waypoints as $waypoint) {
            $waypoint = trim((string) $waypoint);

            if ($waypoint !== '') {
                $intermediates[] = [
                    'address' => $waypoint,
                ];
            }
        }

        $payload = [
            'origin' => [
                'address' => $originAddress,
            ],
            'destination' => [
                'address' => $destinationAddress,
            ],
            'travelMode' => 'DRIVE',
            'routingPreference' => 'TRAFFIC_AWARE',
            'languageCode' => 'fr',
            'units' => 'METRIC',
            'polylineQuality' => 'HIGH_QUALITY',
            'extraComputations' => ['TOLLS'],
        ];

        if (!empty($intermediates)) {
            $payload['intermediates'] = $intermediates;
        }

        \Log::info('Google route payload', $payload);

        $response = Http::withOptions($this->curlIpResolveOptions())->withHeaders([
            'X-Goog-Api-Key' => $apiKey,
            'X-Goog-FieldMask' => implode(',', [
                'routes.distanceMeters',
                'routes.duration',
                'routes.staticDuration',
                'routes.polyline.encodedPolyline',
                'routes.travelAdvisory.tollInfo',
                'routes.legs.distanceMeters',
                'routes.legs.duration',
                'geocodingResults.intermediates',
            ]),
        ])->post($url, $payload);

        if (!$response->successful()) {
            throw new RuntimeException('Google Routes API hatası: ' . $response->body());
        }

        $json = $response->json();
        \Log::info('Google route response', $json);

        $route = Arr::get($json, 'routes.0');

        if (!$route) {
            throw new RuntimeException('Rota bulunamadı.');
        }

        $distanceMeters = (int) Arr::get($route, 'distanceMeters', 0);
        $durationSeconds = $this->parseDurationToSeconds(Arr::get($route, 'duration'));
        $trafficDurationSeconds = $this->parseDurationToSeconds(Arr::get($route, 'staticDuration'));
        [$tollAmount, $tollCurrency] = $this->extractTollPrice($route);

        return [
            'distance_meters' => $distanceMeters,
            'distance_km' => round($distanceMeters / 1000, 1),
            'duration_seconds' => $durationSeconds,
            'duration_text' => $this->secondsToText($durationSeconds),
            'traffic_duration_seconds' => $trafficDurationSeconds,
            'traffic_duration_text' => $this->secondsToText($trafficDurationSeconds),
            'polyline' => Arr::get($route, 'polyline.encodedPolyline'),
            'toll_amount' => $tollAmount,
            'toll_currency' => $tollCurrency,
            'legs_count' => count(Arr::get($json, 'routes.0.legs', [])),
            'raw_response' => $json,
        ];
    }

    private function extractTollPrice(array $route): array
    {
        $prices = Arr::get($route, 'travelAdvisory.tollInfo.estimatedPrice', []);

        if (empty($prices) || !is_array($prices)) {
            return [null, 'EUR'];
        }

        $price = $prices[0];
        $units = (float) Arr::get($price, 'units', 0);
        $nanos = (float) Arr::get($price, 'nanos', 0) / 1000000000;
        $currency = Arr::get($price, 'currencyCode', 'EUR');

        return [round($units + $nanos, 2), $currency];
    }

    private function parseDurationToSeconds(?string $duration): int
    {
        if (!$duration) {
            return 0;
        }

        return (int) rtrim($duration, 's');
    }

    private function secondsToText(int $seconds): string
    {
        if ($seconds <= 0) {
            return '-';
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        if ($hours > 0) {
            return $hours . ' h ' . $minutes . ' min';
        }

        return $minutes . ' min';
    }

    private function curlIpResolveOptions(): array
    {
        if (!defined('CURLOPT_IPRESOLVE') || !defined('CURL_IPRESOLVE_V4')) {
            return [];
        }

        return [
            'curl' => [
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            ],
        ];
    }
}
