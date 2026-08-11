<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Hareket extends Model
{
  use SoftDeletes;
  protected $fillable = [
        'sirket_id','aciklama', 'tarih','post_id','amount','ab','kur_id','acente_id','hareketable_id','hareketable_type','payment_id','offset_id','default_price','urun_id','invoiceno'
       ];
    
  public function hareketable()
    {
        return $this->morphTo();
    }
   public function post(){
         return $this->belongsTo('App\Models\Post');
    }
  public function kur(){
         return $this->belongsTo('App\Models\Kur');
    }
   public function payment(){
         return $this->belongsTo('App\Models\Payment');
    }
  public function acente(){
         return $this->belongsTo('App\Models\Acente');
    }
  public function offset()
  { 
         return $this->belongsTo('App\Models\Offset');  
  }
  public function files()
{
    return $this->hasMany(\App\Models\HareketFile::class, 'hareket_id');
}

}
