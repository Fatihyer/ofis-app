<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kyslik\ColumnSortable\Sortable;

class Invoice extends Model 
{
     protected $fillable = [
          'sirket_id', // Add any other fields you want to allow mass assignment for
          'kur_id',
          'acente_id',
          'tarih',
          'detail',
          'resmi',
          'lang',
          'yazi',
          'account_id',
          'amount',
          'avoir',
          'pennylane_customer_invoice_id',
          'pennylane_customer_invoice_status',
          'pennylane_synced_at',
          'pennylane_payload',
      ];
    use Sortable, SoftDeletes;
    public $sortable = ['id', 'sirket_id',  'acente_id', 'tarih', 'resmi','tarih'];
  public function post(){
         return $this->belongsTo('App\Models\Post')->withTrashed();
    }
    public function kur(){
         return $this->belongsTo('App\Models\Kur');
    }
    public function acente(){
         return $this->belongsTo('App\Models\Acente');
    }
   public function sirket(){
         return $this->belongsTo('App\Models\Sirket');
    }
  public function account(){
         return $this->belongsTo('App\Models\Account');
    }
  public function invoicedetail(){
         return $this->hasMany('App\Models\Invoicedetail');
    }
  public function harekets()
    {
        return $this->morphMany('App\Models\Hareket', 'hareketable');
    }
    public function Groupinvoice()
    {
        return $this->belongsToMany('App\Models\Groupinvoice');
     }  
}
