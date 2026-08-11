<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Depot extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'address',
        'city',
        'postal_code',
        'country',
        'lat',
        'lng',
        'is_default',
        'active',
        'sort_order',
    ];

    protected $casts = [
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'is_default' => 'boolean',
        'active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function vehicules()
    {
        return $this->hasMany(Vehicule::class);
    }

    public function transfers()
    {
        return $this->hasMany(Transfer::class);
    }

    public function talepDays()
    {
        return $this->hasMany(TalepDay::class);
    }
}
