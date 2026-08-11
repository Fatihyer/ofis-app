<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
     protected $fillable = [
        'name','color_id'
       ];
  public function color(){
         return $this->belongsTo('App\Models\Color');
    }
}
