<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
use Kyslik\ColumnSortable\Sortable;

class Acente extends Model
{
  use Sortable;
  use SoftDeletes; 
  protected $fillable = [
        'name','address','ulke_id', 'id', 'tittle','tel','vd','vdno', 'city','color','postal','suivi','whatsapp','airportshuttle','responsable_id','hermescle'
       ];
     
  
   protected $dates = ['deleted_at'];

    public $sortable = ['id', 'name',  'country_id'];
 
     public function scopeFirma($query, $value=null)
    {
        if ($value !== null) {        
       return $query->whereHas('firmas', function ($query) use ($value) {
         $query->where('firma_id',$value);
       });
        }   
    }
    public function scopeSearch($query, $search='*')
          {
            return $query->where('name','like',"%".$search."%");
          }

    public function ulke(){
         return $this->belongsTo('App\Models\Ulke');
    }
    public function firmas()
    {
        return $this->belongsToMany('App\Models\Firma');
     }     
    public function users()
    {
        return $this->belongsToMany('App\Models\User');
    }
  public function msg()
    {
        return $this->hasOne('App\Models\Acentemsg');
     }     
 public function post()
    {
        return $this->hasMany('App\Models\Post');
     }   
  
   public function hareket(){
         return $this->hasMany('App\Models\Hareket');
  } 
  public function invoice(){
         return $this->hasMany('App\Models\Invoice');
  } 
  public function talepler()
  {
    return $this->hasMany('App\Models\Talepler');
  }
  public function emails()
{
    return $this->hasMany(AcenteEmail::class)->orderBy('email');
}
public function responsable()
{
    return $this->belongsTo('App\Models\User', 'responsable_id')->withTrashed();
}
public function driverPlanningProfile()
{
    return $this->hasOne(DriverPlanningProfile::class, 'acente_id');
}
public function driverVehicleCapabilities()
{
    return $this->belongsToMany(Vehicule::class, 'driver_vehicle_capabilities', 'acente_id', 'vehicule_id')
        ->withPivot('preferred', 'notes')
        ->withTimestamps();
}
}

          
