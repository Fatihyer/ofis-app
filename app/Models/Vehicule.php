<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicule extends Model
{
   protected $fillable = [
     'name', 'plaka', 'yil', 'capacity', 'control', 'sigorta',
     'ead_date', 'ext_date', 'lim_date', 'tach_date', 'vid_date',
     'licence_count', 'remarques',
     'sales', 'real', 'enpanne', 'hermes_uid', 'depot_id',
       ];
  public function transfer(){
         return $this->belongsTo('App\Models\Transfer');
    }
  public function kilometers()
{
    return $this->hasMany(Kilometer::class);
}
public function maintenances()
{
    return $this->hasMany(VehicleMaintenance::class);
}
public function fuelPurchases()
{
    return $this->hasMany(FuelPurchase::class);
}
public function depot()
{
    return $this->belongsTo(Depot::class);
}
public function capableDrivers()
{
    return $this->belongsToMany(Acente::class, 'driver_vehicle_capabilities', 'vehicule_id', 'acente_id')
        ->withPivot('preferred', 'notes')
        ->withTimestamps();
}
}
