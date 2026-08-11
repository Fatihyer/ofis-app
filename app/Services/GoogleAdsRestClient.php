<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class GoogleAdsRestClient
{
    private Client $http;
    private array $config;

    public function __construct(?Client $http = null)
    {
        $this->config = config('services.google_ads', []);
        $this->http = $http ?: new Client([
            'timeout' => (int) ($this->config['timeout'] ?? 30),
        ]);
    }

    public function customerId(?string $customerId = null): string
    {
        return $this->normalizeCustomerId($customerId ?: (string) ($this->config['customer_id'] ?? ''));
    }

    public function loginCustomerId(): string
    {
        return $this->normalizeCustomerId((string) ($this->config['login_customer_id'] ?? ''));
    }

    public function apiVersion(): string
    {
        $version = trim((string) ($this->config['api_version'] ?? 'v24'));

        return $version !== '' ? $version : 'v24';
    }

    public function developerTokenLength(): int
    {
        return strlen((string) ($this->config['developer_token'] ?? ''));
    }

    public function listAccessibleCustomers(): array
    {
        $this->assertConfigured(false);

        $response = $this->request('GET', sprintf('%s/customers:listAccessibleCustomers', $this->baseUrl()));

        return $response['resourceNames'] ?? [];
    }

    public function searchStream(string $query, ?string $customerId = null): array
    {
        $this->assertConfigured(true);

        $customerId = $this->customerId($customerId);
        if ($customerId === '') {
            throw new RuntimeException('GOOGLE_ADS_CUSTOMER_ID is missing.');
        }

        $response = $this->request('POST', sprintf(
            '%s/customers/%s/googleAds:searchStream',
            $this->baseUrl(),
            $customerId
        ), [
            'json' => [
                'query' => $query,
            ],
        ]);

        $rows = [];
        foreach ($this->normalizeStreamChunks($response) as $chunk) {
            foreach (($chunk['results'] ?? []) as $row) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    public function mutate(string $resource, array $operations, array $options = [], ?string $customerId = null): array
    {
        $this->assertConfigured(true);

        $customerId = $this->customerId($customerId);
        if ($customerId === '') {
            throw new RuntimeException('GOOGLE_ADS_CUSTOMER_ID is missing.');
        }

        return $this->request('POST', sprintf(
            '%s/customers/%s/%s:mutate',
            $this->baseUrl(),
            $customerId,
            $resource
        ), [
            'json' => array_replace([
                'operations' => $operations,
            ], $options),
        ]);
    }

    public function uploadClickConversions(array $conversions, array $options = [], ?string $customerId = null): array
    {
        $this->assertConfigured(true);

        $customerId = $this->customerId($customerId);
        if ($customerId === '') {
            throw new RuntimeException('GOOGLE_ADS_CUSTOMER_ID is missing.');
        }

        return $this->request('POST', sprintf(
            '%s/customers/%s:uploadClickConversions',
            $this->baseUrl(),
            $customerId
        ), [
            'json' => array_replace([
                'conversions' => $conversions,
                'partialFailure' => true,
                'validateOnly' => false,
            ], $options),
        ]);
    }

    private function assertConfigured(bool $needsCustomer): void
    {
        $missing = [];
        foreach (['developer_token', 'client_id', 'client_secret', 'refresh_token'] as $key) {
            if (trim((string) ($this->config[$key] ?? '')) === '') {
                $missing[] = 'GOOGLE_ADS_' . strtoupper($key);
            }
        }

        if ($needsCustomer && $this->customerId() === '') {
            $missing[] = 'GOOGLE_ADS_CUSTOMER_ID';
        }

        if ($missing !== []) {
            throw new RuntimeException('Missing Google Ads configuration: ' . implode(', ', $missing));
        }
    }

    private function accessToken(): string
    {
        $this->assertConfigured(false);

        $cacheKey = 'google_ads_rest_access_token_' . md5(
            (string) ($this->config['client_id'] ?? '') . '|' . (string) ($this->config['refresh_token'] ?? '')
        );

        return Cache::remember($cacheKey, now()->addMinutes(30), function (): string {
            $response = $this->http->request('POST', 'https://oauth2.googleapis.com/token', [
                'http_errors' => false,
                'form_params' => [
                    'client_id' => $this->config['client_id'],
                    'client_secret' => $this->config['client_secret'],
                    'refresh_token' => $this->config['refresh_token'],
                    'grant_type' => 'refresh_token',
                ],
            ]);

            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            if ($response->getStatusCode() >= 400 || ! is_array($data) || empty($data['access_token'])) {
                throw new RuntimeException('Google OAuth token refresh failed: ' . $this->shortBody($body));
            }

            $ttl = max(60, ((int) ($data['expires_in'] ?? 3600)) - 60);
            Cache::put(
                'google_ads_rest_access_token_' . md5((string) $this->config['client_id'] . '|' . (string) $this->config['refresh_token']),
                $data['access_token'],
                now()->addSeconds($ttl)
            );

            return $data['access_token'];
        });
    }

    private function request(string $method, string $url, array $options = []): array
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken(),
            'developer-token' => (string) $this->config['developer_token'],
            'Accept' => 'application/json',
        ];

        $loginCustomerId = $this->loginCustomerId();
        if ($loginCustomerId !== '') {
            $headers['login-customer-id'] = $loginCustomerId;
        }

        $response = $this->http->request($method, $url, array_replace_recursive([
            'http_errors' => false,
            'headers' => $headers,
        ], $options));

        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        if ($response->getStatusCode() >= 400) {
            throw new RuntimeException(sprintf(
                'Google Ads API error (%s): %s',
                $response->getStatusCode(),
                $this->shortBody($body)
            ));
        }

        if ($data === null && $body !== '' && json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Google Ads API returned invalid JSON: ' . json_last_error_msg());
        }

        return $data ?? [];
    }

    private function baseUrl(): string
    {
        return sprintf('https://googleads.googleapis.com/%s', $this->apiVersion());
    }

    private function normalizeCustomerId(string $customerId): string
    {
        return preg_replace('/\D+/', '', $customerId) ?: '';
    }

    private function normalizeStreamChunks(array $response): array
    {
        if (array_is_list($response)) {
            return $response;
        }

        return [$response];
    }

    private function shortBody(string $body): string
    {
        $body = trim(preg_replace('/\s+/', ' ', $body) ?: '');

        return substr($body, 0, 1200);
    }
}
