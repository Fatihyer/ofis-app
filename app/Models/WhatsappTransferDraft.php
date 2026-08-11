<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappTransferDraft extends Model
{
    protected $table = 'whatsapp_transfer_drafts';

    protected $fillable = [
        'whatsapp_message_id',
        'draft_type',
        'parsed_json',
        'parsed_xml',
        'confidence',
        'status',
        'reviewed_by',
        'reviewed_at',
        'created_transfer_id',
        'created_post_id',
    ];

    protected $casts = [
        'parsed_json' => 'array',
        'reviewed_at' => 'datetime',
        'confidence' => 'decimal:2',
    ];

    public function message()
    {
        return $this->belongsTo(WhatsappMessage::class, 'whatsapp_message_id');
    }
}
