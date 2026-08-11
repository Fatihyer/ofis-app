<?php

namespace App\Http\Controllers;

use App\Jobs\AnalyzeWhatsAppGroupMessageJob;
use App\Models\WhatsAppGroup;
use App\Models\WhatsAppGroupMessage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppGroupWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        if (!$this->validSignature($request)) {
            Log::channel('whatsapp')->warning('Rejected WhatsApp webhook signature');
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $payload = $request->all();
        $message = $payload['message'] ?? [];
        $groupExternalId = (string) ($message['group_id'] ?? '');

        if (!str_ends_with($groupExternalId, '@g.us')) {
            return response()->json(['ok' => true, 'ignored' => 'not_group']);
        }

        $isFromMe = (bool) ($message['is_from_me'] ?? false);

        $group = WhatsAppGroup::updateOrCreate(
            ['external_id' => $groupExternalId],
            [
                'name' => $message['group_name'] ?? $groupExternalId,
                'last_message_at' => $this->sentAt($message['timestamp'] ?? null),
            ]
        );
        $group->refresh();

        if (!$group->is_active) {
            return response()->json(['ok' => true, 'ignored' => 'group_inactive']);
        }

        $externalId = (string) ($message['external_id'] ?? '');
        if ($externalId === '') {
            $externalId = 'wpp_' . sha1($groupExternalId . '|' . ($message['sender_id'] ?? '') . '|' . ($message['timestamp'] ?? '') . '|' . ($message['body'] ?? ''));
        }

        $groupMessage = WhatsAppGroupMessage::updateOrCreate(
            ['external_id' => $externalId],
            [
                'whatsapp_group_id' => $group->id,
                'sender_external_id' => $message['sender_id'] ?? null,
                'sender_phone' => $message['sender_phone'] ?? null,
                'sender_name' => $message['sender_name'] ?? null,
                'direction' => $isFromMe ? WhatsAppGroupMessage::DIRECTION_OUTGOING : WhatsAppGroupMessage::DIRECTION_INCOMING,
                'message_type' => $message['message_type'] ?? 'text',
                'body' => $message['body'] ?? null,
                'sent_at' => $this->sentAt($message['timestamp'] ?? null),
                'analysis_status' => $isFromMe ? WhatsAppGroupMessage::STATUS_NOT_APPLICABLE : WhatsAppGroupMessage::STATUS_PENDING,
                'raw_payload' => $payload,
            ]
        );

        if (!$isFromMe && $groupMessage->wasRecentlyCreated) {
            AnalyzeWhatsAppGroupMessageJob::dispatch($groupMessage->id);
        }

        return response()->json(['ok' => true]);
    }

    private function validSignature(Request $request): bool
    {
        $secret = (string) config('services.whatsapp_gateway.webhook_secret');

        if ($secret === '') {
            return false;
        }

        $signature = (string) $request->header('X-WhatsApp-Signature');
        $signature = str_starts_with($signature, 'sha256=') ? substr($signature, 7) : $signature;
        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }

    private function sentAt($timestamp): ?Carbon
    {
        if (!$timestamp) {
            return now();
        }

        return Carbon::createFromTimestamp((int) $timestamp);
    }
}
