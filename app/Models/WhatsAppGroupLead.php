<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppGroupLead extends Model
{
    public const STATUS_NEW = 'new';
    public const STATUS_REVIEWED = 'reviewed';
    public const STATUS_INTERESTING = 'interesting';
    public const STATUS_QUOTED = 'quoted';
    public const STATUS_CONVERTED = 'converted';
    public const STATUS_NOT_INTERESTED = 'not_interested';
    public const STATUS_DUPLICATE = 'duplicate';

    protected $table = 'whatsapp_group_leads';

    protected $fillable = [
        'whatsapp_group_id',
        'whatsapp_group_message_id',
        'status',
        'score',
        'confidence',
        'sender_phone',
        'sender_name',
        'passenger_count',
        'number_of_vehicles',
        'vehicle_type',
        'service_date',
        'service_time',
        'pickup_location',
        'dropoff_location',
        'request_type',
        'duration',
        'language',
        'summary',
        'possible_duplicate_of_id',
        'assigned_user_id',
        'normalized_hash',
    ];

    protected $casts = [
        'service_date' => 'date',
        'score' => 'integer',
        'confidence' => 'float',
    ];

    public function group()
    {
        return $this->belongsTo(WhatsAppGroup::class, 'whatsapp_group_id');
    }

    public function message()
    {
        return $this->belongsTo(WhatsAppGroupMessage::class, 'whatsapp_group_message_id');
    }

    public function duplicateOf()
    {
        return $this->belongsTo(self::class, 'possible_duplicate_of_id');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id')->withTrashed();
    }
}
