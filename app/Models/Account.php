<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
   protected $fillable = [
        'name','swift','iban', 'kur_id', 'hesapno'
       ]; 
  
  public function kur(){
         return $this->belongsTo('App\Models\Kur');
    }
}
