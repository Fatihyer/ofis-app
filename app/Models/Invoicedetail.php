<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoicedetail extends Model
{
  
    protected $fillable = [
    'invoice_id',
    'post_id',
    'comments',
    'kdv_id',
    'amount',
];   
  
   public function kdv(){
         return $this->belongsTo('App\Models\Kdv');
   }
}
