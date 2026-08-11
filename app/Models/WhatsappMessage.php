<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappMessage extends Model
{
    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'twilio_sid',
        'from_number',
        'to_number',
        'body',
        'raw_payload',
        'status',
    ];

    protected $casts = [
        'raw_payload' => 'array',
    ];

    public function draft()
    {
        return $this->hasOne(WhatsappTransferDraft::class, 'whatsapp_message_id');
    }
}
