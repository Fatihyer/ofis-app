<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PennylaneApiService
{
    private string $baseUrl;
    private ?string $token;
    private int $timeout;
    private string $accountKey = 'francevia';

    private array $accounts = [
        'francevia' => 'France Via',
        'parisvia' => 'Paris Via',
    ];

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.pennylane.base_url', 'https://app.pennylane.com/api/external/v2'), '/');
        $this->timeout = (int) config('services.pennylane.timeout', 20);
        $this->selectAccount($this->accountKey);
    }

    public function accounts(): array
    {
        return $this->accounts;
    }

    public function selectAccount(?string $accountKey): self
    {
        $accountKey = array_key_exists((string) $accountKey, $this->accounts) ? (string) $accountKey : 'francevia';
        $this->accountKey = $accountKey;
        $tokens = (array) config('services.pennylane.tokens', []);
        $this->token = $tokens[$accountKey] ?? config('services.pennylane.token');

        return $this;
    }

    public function accountKey(): string
    {
        return $this->accountKey;
    }

    public function accountLabel(): string
    {
        return $this->accounts[$this->accountKey] ?? $this->accountKey;
    }

    public function configured(): bool
    {
        return !empty($this->token);
    }

    public function get(string $endpoint, array $query = []): array
    {
        if (!$this->configured()) {
            return [
                'ok' => false,
                'status' => null,
                'data' => null,
                'error' => 'Token Pennylane manquant pour '.$this->accountLabel().'. Ajoutez la clé correspondante dans .env.',
            ];
        }

        $endpoint = '/' . ltrim($endpoint, '/');
        $query = array_filter($query, fn ($value) => $value !== null && $value !== '');
        $query['use_2026_api_changes'] = $query['use_2026_api_changes'] ?? true;

        try {
            $response = $this->client()->get($this->baseUrl . $endpoint, $query);

            return [
                'ok' => $response->successful(),
                'status' => $response->status(),
                'data' => $response->json(),
                'error' => $response->successful() ? null : $this->errorMessage($response->json(), $response->body()),
            ];
        } catch (\Throwable $e) {
            Log::warning('Pennylane API read failed', [
                'account' => $this->accountKey,
                'endpoint' => $endpoint,
                'message' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'status' => null,
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function post(string $endpoint, array $payload = [], array $query = []): array
    {
        if (!$this->configured()) {
            return [
                'ok' => false,
                'status' => null,
                'data' => null,
                'error' => 'Token Pennylane manquant pour '.$this->accountLabel().'. Ajoutez la clé correspondante dans .env.',
            ];
        }

        $endpoint = '/' . ltrim($endpoint, '/');
        $query = array_filter($query, fn ($value) => $value !== null && $value !== '');
        $query['use_2026_api_changes'] = $query['use_2026_api_changes'] ?? true;

        try {
            $response = $this->client()->post($this->baseUrl . $endpoint . ($query ? '?' . http_build_query($query) : ''), $payload);

            return [
                'ok' => $response->successful(),
                'status' => $response->status(),
                'data' => $response->json(),
                'error' => $response->successful() ? null : $this->errorMessage($response->json(), $response->body()),
            ];
        } catch (\Throwable $e) {
            Log::warning('Pennylane API write failed', [
                'account' => $this->accountKey,
                'endpoint' => $endpoint,
                'message' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'status' => null,
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function client(): PendingRequest
    {
        return Http::withToken($this->token)
            ->acceptJson()
            ->timeout($this->timeout)
            ->retry(2, 300);
    }

    private function errorMessage($json, string $body): string
    {
        if (is_array($json)) {
            return $json['message'] ?? $json['error'] ?? json_encode($json, JSON_UNESCAPED_UNICODE);
        }

        return $body ?: 'Erreur Pennylane inconnue';
    }
}
