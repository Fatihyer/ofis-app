<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AiPricingClient
{
    private function request(): PendingRequest
    {
        $tokenPath = config('ai_pricing.token_file');
        $token = is_readable($tokenPath) ? trim((string) file_get_contents($tokenPath)) : '';

        if ($token === '') {
            throw new RuntimeException('AI pricing API token is not available.');
        }

        return Http::baseUrl(config('ai_pricing.url'))
            ->withToken($token)
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('ai_pricing.timeout', 5));
    }

    public function health(): array
    {
        return Http::baseUrl(config('ai_pricing.url'))
            ->acceptJson()
            ->timeout((int) config('ai_pricing.timeout', 5))
            ->get('/health')
            ->throw()
            ->json();
    }

    public function predict(array $attributes): array
    {
        return $this->request()
            ->post('/predict', $attributes)
            ->throw()
            ->json();
    }
}

