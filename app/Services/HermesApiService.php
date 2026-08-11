<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HermesApiService
{
    protected $baseUrl = 'https://api.hermesapps.com';
    private const DAILY_LIMIT_KEY = 'hermes_api_daily_limit_until';
    private const STALE_CACHE_PREFIX = 'hermes_api_stale_';

    public function get($endpoint, $params = [])
    {
        $cacheKey = $this->getCacheKey($endpoint, $params);

        if ($limited = $this->dailyLimitPayload()) {
            return $this->cachedGetPayload($cacheKey) ?? $limited;
        }

        return Cache::remember($cacheKey, $this->cacheTtl($endpoint), function () use ($endpoint, $params, $cacheKey) {
            $requestParams = $params;
            $requestParams['api_key'] = config('services.hermes.key');
            $url = "{$this->baseUrl}{$endpoint}";

            try {
                $response = Http::timeout(8)
                    ->connectTimeout(4)
                    ->withOptions([
                        'curl' => [
                            CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
                        ],
                        'allow_redirects' => true,
                    ])->withHeaders([
                        'Accept' => 'application/json',
                    ])->get($url, $requestParams);
            } catch (\Throwable $e) {
                Log::warning('Hermes API GET failed', [
                    'endpoint' => $endpoint,
                    'error' => $e->getMessage(),
                ]);

                $cached = $this->cachedGetPayload($cacheKey) ?? $this->staleGetPayload($cacheKey);
                if ($cached !== null) {
                    Cache::put($cacheKey, $cached, now()->addMinutes(10));
                    return $cached;
                }

                return $this->unavailablePayload($e->getMessage());
            }

            $json = $response->json();

            if ($this->isDailyLimitResponse($json, $response->status())) {
                $this->markDailyLimit($json);
                $cached = $this->cachedGetPayload($cacheKey);
                if ($cached !== null) {
                    Cache::put($cacheKey, $cached, now()->addMinutes(30));
                    return $cached;
                }

                return $this->dailyLimitPayload();
            }

            if (!$response->successful()) {
                $cached = $this->cachedGetPayload($cacheKey) ?? $this->staleGetPayload($cacheKey);
                if ($cached !== null) {
                    Cache::put($cacheKey, $cached, now()->addMinutes(10));
                    return $cached;
                }

                return $this->unavailablePayload('HTTP '.$response->status());
            }

            if (is_array($json)) {
                $this->storeStalePayload($cacheKey, $json);
            }

            return $json;
        });
    }

    public function post($endpoint, $data = [])
    {
        if ($limited = $this->dailyLimitPayload()) {
            return $limited;
        }

        $data['api_key'] = config('services.hermes.key');
        $response = Http::withOptions([
            'curl' => [CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2],
        ])->withHeaders(['Accept' => 'application/json'])
          ->post("{$this->baseUrl}{$endpoint}", $data);

        return $this->handleWriteResponse($response->json(), $response->status());
    }

    public function put($endpoint, $data = [])
    {
        if ($limited = $this->dailyLimitPayload()) {
            return $limited;
        }

        $data['api_key'] = config('services.hermes.key');
        $response = Http::withOptions([
            'curl' => [CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2],
        ])->withHeaders(['Accept' => 'application/json'])
          ->put("{$this->baseUrl}{$endpoint}", $data);

        return $this->handleWriteResponse($response->json(), $response->status());
    }

    public function delete($endpoint)
    {
        if ($limited = $this->dailyLimitPayload()) {
            return $limited;
        }

        $response = Http::withOptions([
            'curl' => [CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2],
        ])->withHeaders(['Accept' => 'application/json'])
          ->delete("{$this->baseUrl}{$endpoint}", ['api_key' => config('services.hermes.key')]);

        return $this->handleWriteResponse($response->json(), $response->status());
    }

    public static function isDailyLimitPayload($payload): bool
    {
        return is_array($payload) && (bool) data_get($payload, '_hermes_daily_limit');
    }

    public static function isUnavailablePayload($payload): bool
    {
        return is_array($payload) && (bool) data_get($payload, '_hermes_unavailable');
    }

    public static function dailyLimitMessage(): ?string
    {
        $until = Cache::get(self::DAILY_LIMIT_KEY);
        if (!$until) {
            return null;
        }

        return "Limite quotidienne Hermes atteinte. Les données Hermes seront réessayées après {$until}.";
    }

    private function handleWriteResponse($json, int $status)
    {
        if ($this->isDailyLimitResponse($json, $status)) {
            $this->markDailyLimit($json);
            return $this->dailyLimitPayload();
        }

        return $json;
    }

    private function dailyLimitPayload(): ?array
    {
        $until = Cache::get(self::DAILY_LIMIT_KEY);
        if (!$until) {
            return null;
        }

        return [
            '_hermes_daily_limit' => true,
            'error' => [
                'code' => 401,
                'message' => "Limite quotidienne Hermes atteinte. Les données Hermes seront réessayées après {$until}.",
            ],
        ];
    }

    private function markDailyLimit($payload): void
    {
        $until = Carbon::now('Europe/Paris')->endOfDay()->addMinutes(5);
        Cache::put(self::DAILY_LIMIT_KEY, $until->format('d/m/Y H:i'), $until);

        Log::warning('Hermes API daily limit reached', [
            'until' => $until->toDateTimeString(),
            'response' => $payload,
        ]);
    }

    private function isDailyLimitResponse($payload, int $status): bool
    {
        $message = (string) data_get($payload, 'error.message', '');

        return $status === 401 && str_contains(strtolower($message), 'api called too much today');
    }


    private function unavailablePayload(string $message): array
    {
        return [
            '_hermes_unavailable' => true,
            'error' => [
                'message' => $message,
            ],
        ];
    }

    private function storeStalePayload(string $cacheKey, array $payload): void
    {
        if ($this->isDailyLimitPayload($payload) || $this->isUnavailablePayload($payload)) {
            return;
        }

        Cache::put(self::STALE_CACHE_PREFIX . $cacheKey, $payload, now()->addHours(12));
    }

    private function staleGetPayload(string $cacheKey): ?array
    {
        $cached = Cache::get(self::STALE_CACHE_PREFIX . $cacheKey);
        if (!is_array($cached)) {
            return null;
        }

        return $cached;
    }

    private function cachedGetPayload(string $cacheKey): ?array
    {
        $cached = Cache::get($cacheKey);
        if (!is_array($cached)) {
            return null;
        }

        return $cached;
    }

    private function cacheTtl(string $endpoint): Carbon
    {
        if (preg_match('#^/units/[^/]+/track-info$#', $endpoint)) {
            return now()->addMinutes(30);
        }

        if ($endpoint === '/units' || $endpoint === '/units/active') {
            return now()->addMinutes(15);
        }

        if (preg_match('#^/units/[^/]+$#', $endpoint)) {
            return now()->addMinutes(15);
        }

        if ($endpoint === '/resources') {
            return now()->addHours(6);
        }

        if (str_contains($endpoint, '/working-time') || str_contains($endpoint, '/resources/')) {
            return now()->addMinutes(30);
        }

        return now()->addMinutes(10);
    }

    private function getCacheKey(string $endpoint, array $params): string
    {
        unset($params['api_key']);
        ksort($params);

        return 'hermes_api_get_' . md5($endpoint . '|' . json_encode($params));
    }
}
