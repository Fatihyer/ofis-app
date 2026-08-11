<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TachographDailySummary extends Model
{
    protected $fillable = [
        'tachograph_import_id',
        'tachograph_driver_card_id',
        'acente_id',
        'card_number',
        'source_date',
        'driving_minutes',
        'work_minutes',
        'availability_minutes',
        'rest_minutes',
        'unknown_minutes',
        'daily_distance_km',
        'summary_uid',
    ];

    protected $casts = [
        'source_date' => 'date',
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
}
