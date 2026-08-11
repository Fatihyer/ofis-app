<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Efatura extends Model
{
 protected $fillable = ['fatno','name','tutar','date','price','acente_id', 'senaryo','durum','tip','vd' ]; //<---- Add this line
  
   public function acente(){
         return $this->belongsTo('App\Models\Acente');
    } 
   
}