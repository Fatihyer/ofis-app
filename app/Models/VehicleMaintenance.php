<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleMaintenance extends Model
{


    protected $fillable = [
        'vehicule_id',
        'description',
        'service_date',
        'amount',
        'category',
        'acente_id',
        'payment_id',
        'kur_id',
        'invoiceno',
        'hareket_id',
    ];

    public function vehicule()
    {
        return $this->belongsTo(Vehicule::class);
    }

    public function acente()
    {
        return $this->belongsTo(Acente::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function kur()
    {
        return $this->belongsTo(Kur::class);
    }

    public function hareket()
    {
        return $this->belongsTo(Hareket::class);
    }

    public function harekets()
    {
        return $this->morphMany(Hareket::class, 'hareketable');
    }
}
