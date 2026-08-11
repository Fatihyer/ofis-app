<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Groupinvoice extends Model
{
     protected $fillable = [
        'tarih',
        'resmi',
        'sirket_id',
       ]; 
  public function invoices()
    {
        return $this->belongsToMany('App\Models\Invoice');
     }  
     public function sirket(){
          return $this->belongsTo('App\Models\Sirket');
     }    
}
