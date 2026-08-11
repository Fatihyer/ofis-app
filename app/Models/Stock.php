<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $fillable = [
        'tarih',
        'post_id',
        'urun_id',
        'adet',
        'movement_type',
        'affects_stock',
        'a_acente_id',
        'b_acente_id',
        'aciklama',
        'ab',
        'credit',
        'offset_id',
        'sell_price',
        'buy_price',
    ];

    protected $casts = [
        'tarih' => 'date',
        'affects_stock' => 'boolean',
    ];

    public function harekets()
    {
        return $this->morphMany('App\Models\Hareket', 'hareketable');
    }
  public function urun(){
         return $this->belongsTo('App\Models\Acente','urun_id');
    }
   public function aAcente()
  {
      return $this->belongsTo('App\Models\Acente', 'a_acente_id');
  }
     public function bAcente()
  {
      return $this->belongsTo('App\Models\Acente', 'b_acente_id');
  }
  
    public function offset()
  {
      return $this->belongsTo('App\Models\Offset');
  }
}
