<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
      protected $fillable = [
      'title','name','post_id','surname','tel','email','comments'
       ];
  
  public function post(){
         return $this->belongsTo('App\Models\Post');
    }
}
