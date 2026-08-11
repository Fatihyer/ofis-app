<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Other extends Model
{
    protected $fillable = [
        'acente_id','from','post_id','to','comment','pax'
       ];
  
  public function acente(){
         return $this->belongsTo('App\Models\Acente');
    }
  
    public function harekets()
    {
        return $this->morphMany('App\Models\Hareket', 'hareketable');
    }
}
