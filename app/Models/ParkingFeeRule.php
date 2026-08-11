<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParkingFeeRule extends Model
{
    use HasFactory;

    protected $table = 'parking_fee_rules';

    protected $fillable = [
        'location_key',
        'label',
        'vehicle_type',
        'amount',
        'currency',
        'notes',
        'active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'active' => 'boolean',
    ];
}