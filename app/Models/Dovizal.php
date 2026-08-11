<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dovizal extends Model
{
    protected $fillable = [
        'value','kur_id','tarih'
       ];

public function kur(){
         return $this->belongsTo('App\Models\Kur');
    }

}
