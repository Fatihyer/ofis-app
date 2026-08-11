<?php

namespace App\Notifications;

use App\Models\WhatsAppGroupLead;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WhatsAppGroupLeadDetectedNotification extends Notification
{
    use Queueable;

    public function __construct(private WhatsAppGroupLead $lead)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $lead = $this->lead->loadMissing('group');

        return [
            'type' => 'whatsapp_group_lead',
            'message' => 'WhatsApp iş talebi: ' . ($lead->summary ?: ('Score ' . $lead->score)),
            'url' => route('whatsapp-group-leads.show', $lead),
            'score' => $lead->score,
            'group' => $lead->group?->name,
        ];
    }
}
