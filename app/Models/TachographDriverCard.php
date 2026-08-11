<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TachographDriverCard extends Model
{
    protected $fillable = [
        'card_number',
        'acente_id',
        'driver_first_name',
        'driver_last_name',
        'preferred_language',
        'last_imported_at',
    ];

    protected $casts = [
        'last_imported_at' => 'datetime',
    ];

    public function acente()
    {
        return $this->belongsTo(Acente::class);
    }

    public function imports()
    {
        return $this->hasMany(TachographImport::class);
    }

    public function drives()
    {
        return $this->hasMany(TachographDrive::class);
    }
}
