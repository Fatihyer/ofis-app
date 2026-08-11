<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;
use Illuminate\Database\Eloquent\SoftDeletes;



class Transfer extends Model 
{
     use SoftDeletes;
     
     protected $casts = [
          'vehicle_locked' => 'boolean',
          'vehicle_locked_at' => 'datetime',
          'ofis_start' => 'datetime',
          'start_date' => 'datetime',
          'end_date' => 'datetime',
          'driver_app_confirmed_at' => 'datetime',
          'driver_app_refused_at' => 'datetime',
          'second_driver_app_confirmed_at' => 'datetime',
          'second_driver_app_refused_at' => 'datetime',
          'driver_app_reconfirm_required_at' => 'datetime',
          'second_driver_app_reconfirm_required_at' => 'datetime',
      ];
     use Sortable;
     protected $fillable = [
          'start_date', 'end_date', 'ofis_start', 'depot_id', 'depot_source', 'vehicule_id', 'vehicle_locked', 'vehicle_locked_at', 'vehicle_locked_by', 'vehicle_provider_acente_id', 'external_vehicle_note',
          'external_vehicle_price', 'post_id', 'servicetype_id', 'driver_id', 'second_driver_id', 'from', 'target',
          'pax', 'comments', 'mission', 'accueil', 'km', 'status_id', 'conge',
      ];
   
    public $sortable = ['id', 'start_date', 'driver_id', 'vehicule_id', 'from', 'updated_at', 'post_id','status_id'];
  
  
    public function post(){
         return $this->belongsTo('App\Models\Post');
    }
    public function vehicule(){
         return $this->belongsTo('App\Models\Vehicule');
    }
    public function depot(){
         return $this->belongsTo('App\Models\Depot');
    }
   public function driver(){
         return $this->belongsTo('App\Models\Acente','driver_id');
    }
  public function secondDriver(){
         return $this->belongsTo('App\Models\Acente','second_driver_id');
    }
  public function externalVehicleProvider(){
         return $this->belongsTo('App\Models\Acente','vehicle_provider_acente_id');
    }
  public function servicetype(){
         return $this->belongsTo('App\Models\Servicetype');
    }
  public function harekets()
    {
        return $this->morphMany('App\Models\Hareket', 'hareketable');
    }
  public function status(){
         return $this->belongsTo('App\Models\Status');
    }
  public function missionr(){
     return $this->hasOne('App\Models\Mission','transfer_id');
 }
 public function trajets()
{
    return $this->hasMany(Trajet::class)->orderBy('order')->orderBy('datetime')->orderBy('id');
}
public function transferDriverHarekets()
{
    return $this->hasMany(TransferDriverHareket::class);
}

public function getCallStatusAttribute()
{
    if ($this->driver_confirmed_at) return 'confirmed';
    if ($this->called_at) return 'called';
    if ($this->sms_sent_at) return 'sms_sent';
    return 'pending';
}


}
