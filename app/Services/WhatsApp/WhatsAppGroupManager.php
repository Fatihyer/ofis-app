<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsAppGroupProviderInterface;
use App\Models\WhatsAppGroup;
use App\Models\WhatsAppGroupMessage;
use Illuminate\Support\Facades\Log;

class WhatsAppGroupManager
{
    public function __construct(private WhatsAppGroupProviderInterface $provider)
    {
    }

    public function status(): array
    {
        return $this->provider->status();
    }

    public function syncGroups(): int
    {
        $count = 0;

        foreach ($this->provider->groups() as $group) {
            if (empty($group['external_id'])) {
                continue;
            }

            WhatsAppGroup::updateOrCreate(
                ['external_id' => $group['external_id']],
                [
                    'name' => $group['name'] ?? $group['external_id'],
                    'participants_count' => $group['participants_count'] ?? null,
                ]
            );

            $count++;
        }

        return $count;
    }

    public function qr(): array
    {
        return $this->provider->qr();
    }

    public function sendGroupMessage(WhatsAppGroup $group, string $message, int $userId): WhatsAppGroupMessage
    {
        $response = $this->provider->sendGroupMessage($group->external_id, $message);

        if (!($response['ok'] ?? false)) {
            Log::channel('whatsapp')->warning('WPPConnect group send failed', [
                'group_id' => $group->id,
                'error' => $response['error'] ?? 'unknown',
            ]);

            throw new \RuntimeException('Mesaj gönderilemedi. WhatsApp bağlantısını kontrol edin.');
        }

        return WhatsAppGroupMessage::create([
            'whatsapp_group_id' => $group->id,
            'external_id' => $response['message_id'] ?? ('out_' . $group->id . '_' . now()->timestamp . '_' . bin2hex(random_bytes(4))),
            'direction' => WhatsAppGroupMessage::DIRECTION_OUTGOING,
            'message_type' => 'text',
            'body' => $message,
            'sent_at' => now(),
            'analysis_status' => WhatsAppGroupMessage::STATUS_NOT_APPLICABLE,
            'sent_by_user_id' => $userId,
            'raw_payload' => $response,
        ]);
    }
}
