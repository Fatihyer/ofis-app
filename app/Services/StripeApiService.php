<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StripeApiService
{
    private string $baseUrl = 'https://api.stripe.com/v1';
    private ?string $secret;
    private int $timeout;
    private string $accountKey = 'parisvia';

    public function __construct()
    {
        $this->timeout = (int) config('services.stripe.timeout', 20);
        $this->selectAccount($this->accountKey);
    }

    public function selectAccount(?string $accountKey): self
    {
        $accounts = (array) config('services.stripe.accounts', []);
        $accountKey = array_key_exists((string) $accountKey, $accounts) ? (string) $accountKey : 'parisvia';
        $this->accountKey = $accountKey;
        $this->secret = $accounts[$accountKey]['secret'] ?? config('services.stripe.secret');

        return $this;
    }

    public function accountKey(): string
    {
        return $this->accountKey;
    }

    public function configured(): bool
    {
        return !empty($this->secret);
    }

    public function listSucceededPaymentIntents(int $createdGte, int $createdLte, int $maxPages = 10): array
    {
        if (!$this->configured()) {
            return ['ok' => false, 'error' => 'Clé STRIPE_SECRET manquante dans .env.', 'items' => []];
        }

        $items = [];
        $startingAfter = null;
        $pages = 0;

        do {
            $query = [
                'limit' => 100,
                'created' => [
                    'gte' => $createdGte,
                    'lte' => $createdLte,
                ],
                'expand' => [
                    'data.customer',
                    'data.latest_charge',
                ],
            ];

            if ($startingAfter) {
                $query['starting_after'] = $startingAfter;
            }

            $result = $this->get('/payment_intents', $query);
            if (!($result['ok'] ?? false)) {
                return ['ok' => false, 'error' => $result['error'] ?? 'Erreur Stripe inconnue.', 'items' => $items];
            }

            $data = $result['data'];
            foreach (($data['data'] ?? []) as $paymentIntent) {
                if (($paymentIntent['status'] ?? null) === 'succeeded') {
                    $items[] = $paymentIntent;
                }
            }

            $last = end($data['data']);
            $startingAfter = is_array($last) ? ($last['id'] ?? null) : null;
            $hasMore = (bool) ($data['has_more'] ?? false);
            $pages++;
        } while ($hasMore && $startingAfter && $pages < $maxPages);

        return ['ok' => true, 'items' => $items, 'error' => null];
    }

    private function get(string $endpoint, array $query = []): array
    {
        try {
            $response = Http::withBasicAuth((string) $this->secret, '')
                ->acceptJson()
                ->timeout($this->timeout)
                ->retry(2, 300)
                ->get($this->baseUrl . $endpoint, $query);

            return [
                'ok' => $response->successful(),
                'data' => $response->json(),
                'error' => $response->successful() ? null : $this->errorMessage($response->json(), $response->body()),
            ];
        } catch (\Throwable $e) {
            Log::warning('Stripe API read failed', [
                'endpoint' => $endpoint,
                'message' => $e->getMessage(),
            ]);

            return ['ok' => false, 'data' => null, 'error' => $e->getMessage()];
        }
    }

    private function errorMessage($json, string $body): string
    {
        if (is_array($json)) {
            return data_get($json, 'error.message') ?? json_encode($json, JSON_UNESCAPED_UNICODE);
        }

        return $body ?: 'Erreur Stripe inconnue';
    }
}
