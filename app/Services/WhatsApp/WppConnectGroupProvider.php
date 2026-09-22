<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsAppGroupProviderInterface;
use Illuminate\Support\Facades\Http;

class WppConnectGroupProvider implements WhatsAppGroupProviderInterface
{
    public function status(): array
    {
        return $this->request()->get($this->url('/health'))->throw()->json();
    }

    public function groups(): array
    {
        return $this->request()->get($this->url('/api/groups'))->throw()->json('groups') ?? [];
    }

    public function qr(): array
    {
        return $this->request()->get($this->url('/api/qr'))->throw()->json();
    }

    public function sendGroupMessage(string $groupId, string $message): array
    {
        return $this->request()
            ->post($this->url('/api/groups/send'), [
                'group_id' => $groupId,
                'message' => $message,
            ])
            ->throw()
            ->json();
    }

    public function history(string $groupId, int $limit = 0, ?int $since = null): array
    {
        return $this->request()
            ->timeout((int) config('services.whatsapp_gateway.history_timeout', 300))
            ->get($this->url('/api/groups/' . $groupId . '/history'), array_filter([
                'limit' => $limit ?: null,
                'since' => $since,
            ]))
            ->throw()
            ->json('messages') ?? [];
    }

    private function request()
    {
        return Http::timeout((int) config('services.whatsapp_gateway.timeout', 20))
            ->acceptJson()
            ->withToken((string) config('services.whatsapp_gateway.token'));
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.whatsapp_gateway.url'), '/') . $path;
    }
}
