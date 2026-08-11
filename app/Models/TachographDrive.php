<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TachographDrive extends Model
{
    protected $fillable = [
        'tachograph_import_id',
        'tachograph_driver_card_id',
        'acente_id',
        'transfer_id',
        'card_number',
        'source_date',
        'started_at',
        'ended_at',
        'duration_minutes',
        'daily_distance_km',
        'activity_uid',
        'match_status',
        'match_score',
        'match_reason',
    ];

    protected $casts = [
        'source_date' => 'date',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'daily_distance_km' => 'decimal:2',
    ];

    public function import()
    {
        return $this->belongsTo(TachographImport::class, 'tachograph_import_id');
    }

    public function card()
    {
        return $this->belongsTo(TachographDriverCard::class, 'tachograph_driver_card_id');
    }

    public function acente()
    {
        return $this->belongsTo(Acente::class);
    }

    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }
}
