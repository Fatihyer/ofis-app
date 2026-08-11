<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kilometer extends Model
{
    protected $fillable = [
        'vehicule_id',
        'kilometer',
    ];
    
    public function vehicule()
    {
        return $this->belongsTo(Vehicule::class);
    }
}
