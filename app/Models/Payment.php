<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'name','cari','color_id'
       ];
   public function color(){
         return $this->belongsTo('App\Models\Color');
    }
}
