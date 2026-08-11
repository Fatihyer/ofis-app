<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Offset extends Model
{

  protected $fillable = [
        'sirket_id',
        'tarih',
        'a_acente_id',
        'b_acente_id',
        'aciklama',
        
        // diğer alanlar…
    ];
     public function alacakli()
  {
      return $this->belongsTo('App\Models\Acente', 'a_acente_id');
  }
     public function borclu()
  {
      return $this->belongsTo('App\Models\Acente', 'b_acente_id');
  }
  
  public function harekets()
    {
        return $this->morphMany('App\Models\Hareket', 'hareketable');
    }
  

}
