<?php

namespace App\Jobs;

use App\Contracts\WhatsAppLeadAnalyzerInterface;
use App\Models\User;
use App\Models\WhatsAppGroupLead;
use App\Models\WhatsAppGroupMessage;
use App\Notifications\WhatsAppGroupLeadDetectedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class AnalyzeWhatsAppGroupMessageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $messageId)
    {
    }

    public function handle(WhatsAppLeadAnalyzerInterface $analyzer): void
    {
        $message = WhatsAppGroupMessage::with('group')->find($this->messageId);

        if (!$message || !$message->group) {
            return;
        }

        if (!$message->group->is_active || !$message->group->analysis_enabled) {
            $message->update(['analysis_status' => WhatsAppGroupMessage::STATUS_IGNORED]);
            return;
        }

        if ($message->direction !== WhatsAppGroupMessage::DIRECTION_INCOMING || $message->message_type !== 'text') {
            $message->update(['analysis_status' => WhatsAppGroupMessage::STATUS_NOT_APPLICABLE]);
            return;
        }

        try {
            $result = $analyzer->analyze($message);

            if (!$result->isLead) {
                $message->update(['analysis_status' => WhatsAppGroupMessage::STATUS_IGNORED]);
                return;
            }

            $hash = $this->normalizedHash((string) $message->body, $result->serviceDate, $result->pickupLocation, $result->dropoffLocation);
            $duplicate = $hash ? WhatsAppGroupLead::where('normalized_hash', $hash)->where('whatsapp_group_message_id', '!=', $message->id)->first() : null;

            $lead = WhatsAppGroupLead::updateOrCreate(
                ['whatsapp_group_message_id' => $message->id],
                array_merge($result->toLeadAttributes(), [
                    'whatsapp_group_id' => $message->whatsapp_group_id,
                    'status' => $duplicate ? WhatsAppGroupLead::STATUS_DUPLICATE : WhatsAppGroupLead::STATUS_NEW,
                    'sender_phone' => $message->sender_phone,
                    'sender_name' => $message->sender_name,
                    'possible_duplicate_of_id' => $duplicate?->id,
                    'normalized_hash' => $hash,
                ])
            );

            $message->update(['analysis_status' => WhatsAppGroupMessage::STATUS_ANALYZED]);

            if ($lead->score >= 70 && !$duplicate) {
                $this->notifyHighPriorityLead($lead);
            }
        } catch (\Throwable $e) {
            $message->update(['analysis_status' => WhatsAppGroupMessage::STATUS_FAILED]);

            Log::channel('whatsapp')->error('WhatsApp group message analysis failed', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function notifyHighPriorityLead(WhatsAppGroupLead $lead): void
    {
        $users = User::permission('whatsapp-leads.view')->get();

        if ($users->isEmpty()) {
            $users = User::role('Superadmin')->get();
        }

        Notification::send($users, new WhatsAppGroupLeadDetectedNotification($lead));
    }

    private function normalizedHash(string $body, ?string $date, ?string $pickup, ?string $dropoff): string
    {
        $text = mb_strtolower($body . ' ' . $date . ' ' . $pickup . ' ' . $dropoff);
        $text = preg_replace('/[^\pL\pN]+/u', ' ', $text);
        $text = preg_replace('/\s+/', ' ', trim($text));

        return hash('sha256', $text);
    }
}
