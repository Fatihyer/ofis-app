<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppQuickReply extends Model
{
    protected $table = 'whatsapp_quick_replies';

    protected $fillable = [
        'title',
        'body',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
