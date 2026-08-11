<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TachographImport extends Model
{
    protected $fillable = [
        'tachograph_driver_card_id',
        'acente_id',
        'imported_by',
        'file_hash',
        'original_filename',
        'stored_path',
        'card_number',
        'driver_first_name',
        'driver_last_name',
        'vehicle_plates',
        'issuing_authority',
        'activity_from',
        'activity_to',
        'records_count',
        'drives_count',
        'matched_count',
        'ignored_drives_count',
        'total_driving_minutes',
        'total_distance_km',
        'duplicate_of_id',
        'imported_at',
    ];

    protected $casts = [
        'vehicle_plates' => 'array',
        'activity_from' => 'date',
        'activity_to' => 'date',
        'total_distance_km' => 'decimal:2',
        'imported_at' => 'datetime',
    ];

    public function card()
    {
        return $this->belongsTo(TachographDriverCard::class, 'tachograph_driver_card_id');
    }

    public function acente()
    {
        return $this->belongsTo(Acente::class);
    }

    public function drives()
    {
        return $this->hasMany(TachographDrive::class);
    }

    public function dailySummaries()
    {
        return $this->hasMany(TachographDailySummary::class);
    }
}
