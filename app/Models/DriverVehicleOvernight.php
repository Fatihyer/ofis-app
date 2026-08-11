<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DriverVehicleOvernight extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'overnight_date',
        'driver_id',
        'vehicule_id',
        'transfer_id',
        'post_id',
        'city',
        'address',
        'google_address',
        'reason',
        'notes',
        'amount',
        'created_by',
    ];

    protected $casts = [
        'overnight_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function driver()
    {
        return $this->belongsTo(Acente::class, 'driver_id');
    }

    public function vehicule()
    {
        return $this->belongsTo(Vehicule::class, 'vehicule_id');
    }

    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
