<?php
  
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Fromexcel extends Model
{
 protected $fillable = ['tarih','dekont','tutar','aciklama','etiket','acente_id', 'kur_id' ]; //<---- Add this line
  
   public function acente(){
         return $this->belongsTo('App\Models\Acente');
    } 
   public function kur(){
         return $this->belongsTo('App\Models\Kur');
    }
}
