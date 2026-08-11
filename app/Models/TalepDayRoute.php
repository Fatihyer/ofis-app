<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TalepDayRoute extends Model
{
    use HasFactory;

    protected $table = 'talep_day_routes';

    protected $fillable = [
        'talep_day_id',
        'provider',
        'distance_meters',
        'duration_seconds',
        'traffic_duration_seconds',
        'toll_amount',
        'toll_currency',
        'polyline',
        'raw_response',
        'calculated_at',
    ];

    protected $casts = [
        'distance_meters' => 'integer',
        'duration_seconds' => 'integer',
        'traffic_duration_seconds' => 'integer',
        'toll_amount' => 'decimal:2',
        'calculated_at' => 'datetime',
        'raw_response' => 'array',
    ];

    public function day()
    {
        return $this->belongsTo(TalepDay::class, 'talep_day_id');
    }
}