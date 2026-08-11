<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
    protected $fillable = [
        'acente_id','status_id','from','to','sng','dbl','post_id','trp','qtr','fam','servicetype_id','chd','chdyears','comment','pax'
       ];
  
  public function acente(){
         return $this->belongsTo('App\Models\Acente');
    }
    public function post(){
        return $this->belongsTo('App\Models\Post');
   }

   public function servicetype(){
         return $this->belongsTo('App\Models\Servicetype');
    }
    public function harekets()
    {
        return $this->morphMany('App\Models\Hareket', 'hareketable');
    }
}
