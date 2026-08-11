<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Firma extends Model
{
    protected $fillable = [
        'name','color_id'
       ];
  public function color(){
         return $this->belongsTo('App\Models\Color');
    }
   public function acentes()
    {
        return $this->belongsToMany('App\Models\Acente')->orderBy('name');
    }   
}
