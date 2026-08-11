<?php
  
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class FuelExcel extends Model
{

    protected $fillable = [
        'vehicule_raw',
        'card_raw',
        'authorized_at',
        'location',
        'volume',
        'amount',
        'fuel_type',
        'original_data',
        'kilometrage'
    ];
   
   
}
