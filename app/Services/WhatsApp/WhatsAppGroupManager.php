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
                    'name' => $this->displayName($group['name'] ?? null),
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

    /**
     * Gateway'den bir grubun gecmis mesajlarini cekip veritabanina yazar.
     * Mevcut kayitlar external_id uzerinden guncellenir, kopya olusmaz.
     */
    public function importHistory(WhatsAppGroup $group, int $limit = 0, ?int $since = null): int
    {
        $imported = 0;

        foreach ($this->provider->history($group->external_id, $limit, $since) as $message) {
            $externalId = (string) ($message['external_id'] ?? '');

            if ($externalId === '') {
                continue;
            }

            $isFromMe = (bool) ($message['is_from_me'] ?? false);

            WhatsAppGroupMessage::updateOrCreate(
                ['external_id' => $externalId],
                [
                    'whatsapp_group_id' => $group->id,
                    'sender_external_id' => $message['sender_id'] ?? null,
                    'sender_phone' => $message['sender_phone'] ?? null,
                    'sender_name' => $message['sender_name'] ?? null,
                    'direction' => $isFromMe ? WhatsAppGroupMessage::DIRECTION_OUTGOING : WhatsAppGroupMessage::DIRECTION_INCOMING,
                    'message_type' => $message['message_type'] ?? 'text',
                    'body' => $message['body'] ?? null,
                    'sent_at' => !empty($message['timestamp']) ? now()->setTimestamp((int) $message['timestamp']) : null,
                    'analysis_status' => WhatsAppGroupMessage::STATUS_NOT_APPLICABLE,
                ]
            );

            $imported++;
        }

        if ($imported > 0) {
            $group->update([
                'last_message_at' => WhatsAppGroupMessage::where('whatsapp_group_id', $group->id)->max('sent_at'),
            ]);
        }

        return $imported;
    }

    private function displayName($value): string
    {
        $name = trim((string) $value);

        if ($name === '' || preg_match('/@(lid|g\.us|c\.us)$/i', $name) || preg_match('/^\d{8,}$/', $name)) {
            return 'Groupe WhatsApp';
        }

        return $name;
    }
}
