<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehiclePriceRule extends Model
{
    use HasFactory;

    protected $table = 'vehicle_price_rules';

    protected $fillable = [
        'vehicle_type',
        'service_type',
        'base_rate',
        'included_km',
        'included_hours',
        'extra_km_rate',
        'extra_hour_rate',
        'night_extra_hour_rate',
        'minimum_charge',
        'driver_meal_cost',
        'driver_hotel_cost',
        'default_margin_percent',
        'vat_rate',
        'active',
    ];

    protected $casts = [
        'base_rate' => 'decimal:2',
        'included_km' => 'integer',
        'included_hours' => 'decimal:2',
        'extra_km_rate' => 'decimal:2',
        'extra_hour_rate' => 'decimal:2',
        'night_extra_hour_rate' => 'decimal:2',
        'minimum_charge' => 'decimal:2',
        'driver_meal_cost' => 'decimal:2',
        'driver_hotel_cost' => 'decimal:2',
        'default_margin_percent' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'active' => 'boolean',
    ];
}