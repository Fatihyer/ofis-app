<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;

class Servicetype extends Model
{
    protected $fillable = [
        'name','color_id','firma_id'
       ];
       use Sortable;
  public function color(){
         return $this->belongsTo('App\Models\Color');
    }
  public function firma(){
         return $this->belongsTo('App\Models\Firma');
    }
}
