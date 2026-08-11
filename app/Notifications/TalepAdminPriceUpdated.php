<?php

namespace App\Notifications;

use App\Models\Talep;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TalepAdminPriceUpdated extends Notification
{
    use Queueable;

    public function __construct(
        private Talep $talep,
        private ?float $oldPrice,
        private ?float $newPrice,
        private ?string $updatedBy
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $currency = $this->talep->currency ?: 'EUR';

        return [
            'kind' => 'talep_admin_price_updated',
            'title' => 'Prix admin mis à jour',
            'message' => 'Le prix admin de la demande #'.$this->talep->id.' a été mis à jour.',
            'talep_id' => $this->talep->id,
            'request_no' => $this->talep->request_no,
            'customer_name' => $this->talep->customer_name,
            'old_price' => $this->oldPrice,
            'new_price' => $this->newPrice,
            'currency' => $currency,
            'updated_by' => $this->updatedBy,
            'url' => route('talepler.show', $this->talep->id),
        ];
    }
}
