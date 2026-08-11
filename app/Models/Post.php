<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model 
{
use SoftDeletes;
     use Sortable;
     protected $fillable = [
        'title', 'body','start_date','end_date','acente_id','pax','child','resmi','status_id','user_id','billing_status','payment_destination','payment_status'
       ];

    public $sortable = ['id', 'start_date',  'acente_id', 'updated_at','start_date','status_id'];
  
  public function acente(){
         return $this->belongsTo('App\Models\Acente');
    }
    public function user(){
     return $this->belongsTo('App\Models\User');
}
  public function client(){
         return $this->hasMany('App\Models\Client');
    }
   public function servicetype(){
         return $this->belongsTo('App\Models\Servicetype');
    }
  public function status(){
         return $this->belongsTo('App\Models\Status');
    }
  public function message(){
         return $this->belongsTo('App\Models\Message');
    }
  public function transfer(){
         return $this->hasMany('App\Models\Transfer')->orderBy('start_date');
    }
  public function vehicule(){
         return $this->belongsTo('App\Models\Vehicule');  
    }
   public function hareket(){
         return $this->hasMany('App\Models\Hareket')->orderBy('tarih');  
    }
   public function toplahareket(){
      return $this->hasMany('App\Models\Hareket')->selectRaw('kurs.*,sum(amount) as sumamounts')->groupBy('kur_id');
   }
   public function invoice(){
         return $this->hasMany('App\Models\Invoice'); 
   }  
  public function stock(){
         return $this->hasMany('App\Models\Stock');  
    } 
   public function hotels(){
         return $this->hasMany('App\Models\Hotel');  
    } 
    public function others(){
         return $this->hasMany('App\Models\Other');  
    }
     
     
  
}
