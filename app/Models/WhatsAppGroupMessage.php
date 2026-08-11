<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppGroupMessage extends Model
{
    public const DIRECTION_INCOMING = 'incoming';
    public const DIRECTION_OUTGOING = 'outgoing';

    public const STATUS_PENDING = 'pending';
    public const STATUS_ANALYZED = 'analyzed';
    public const STATUS_IGNORED = 'ignored';
    public const STATUS_FAILED = 'failed';
    public const STATUS_NOT_APPLICABLE = 'not_applicable';

    protected $table = 'whatsapp_group_messages';

    protected $fillable = [
        'whatsapp_group_id',
        'external_id',
        'sender_external_id',
        'sender_phone',
        'sender_name',
        'direction',
        'message_type',
        'body',
        'sent_at',
        'analysis_status',
        'raw_payload',
        'sent_by_user_id',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'sent_at' => 'datetime',
    ];

    public function group()
    {
        return $this->belongsTo(WhatsAppGroup::class, 'whatsapp_group_id');
    }

    public function lead()
    {
        return $this->hasOne(WhatsAppGroupLead::class, 'whatsapp_group_message_id');
    }

    public function sentBy()
    {
        return $this->belongsTo(User::class, 'sent_by_user_id')->withTrashed();
    }
}
