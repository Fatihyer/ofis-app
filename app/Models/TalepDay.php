<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TalepDay extends Model
{
    use HasFactory;

    protected $table = 'talep_days';

    protected $fillable = [
        'talep_id',
        'depot_id',
        'day_number',
        'service_date',
        'start_time',
        'end_time',
        'service_type',
        'vehicle_type',
        'pax',
        'luggage',
        'pickup_location',
        'dropoff_location',
        'route_description',
        'via_points_json',
        'flight_number',
        'notes',
        'note_equipe',
        'note_admin',
        'system_price',
        'ai_suggested_price',
        'final_price',
        'admin_price',
        'admin_price_user_id',
        'admin_price_updated_at',
        'duration_seconds',
        'traffic_duration_seconds',
        'polyline',
        'distance_meters',
        'toll_amount',
        'toll_currency',
        'decoucher',
        'parking',
        'checkpoint',
        'fuel_amount',
        'fuel_liters',
    ];

    protected $casts = [
        'service_date' => 'date',
        'pax' => 'integer',
        'system_price' => 'decimal:2',
        'ai_suggested_price' => 'decimal:2',
        'final_price' => 'decimal:2',
        'admin_price' => 'decimal:2',
        'admin_price_updated_at' => 'datetime',
        'toll_amount' => 'decimal:2',
        'decoucher' => 'decimal:2',
        'parking' => 'decimal:2',
        'checkpoint' => 'decimal:2',
        'fuel_amount' => 'decimal:2',
        'fuel_liters' => 'decimal:2',
        'depot_id' => 'integer',
        'via_points_json' => 'array',
    ];

    public function talep()
    {
        return $this->belongsTo(Talep::class, 'talep_id');
    }

    public function depot()
    {
        return $this->belongsTo(Depot::class, 'depot_id');
    }

    public function route()
    {
        return $this->hasOne(TalepDayRoute::class, 'talep_day_id')->latestOfMany();
    }
}
