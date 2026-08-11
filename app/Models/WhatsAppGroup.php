<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppGroup extends Model
{
    protected $table = 'whatsapp_groups';

    protected $fillable = [
        'external_id',
        'name',
        'participants_count',
        'is_active',
        'analysis_enabled',
        'last_message_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'analysis_enabled' => 'boolean',
        'last_message_at' => 'datetime',
    ];

    public function messages()
    {
        return $this->hasMany(WhatsAppGroupMessage::class, 'whatsapp_group_id');
    }

    public function leads()
    {
        return $this->hasMany(WhatsAppGroupLead::class, 'whatsapp_group_id');
    }
}
