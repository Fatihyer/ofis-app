<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mission extends Model
{
    protected $fillable = [
      'transfer_id', 'hareket','user_id','confirmed_at', 'depart_km','finish_km','startlocalisation','start_user_id','surPlacelocalisation','cleaning_status',
      'office_note', 'office_note_updated_at', 'office_note_updated_by'
     
       ];
   protected $casts = [
      'office_note_updated_at' => 'datetime',
   ];
   public function transfer(){
        return $this->belongsTo('App\Models\Transfer');
   } 
   public function user(){
      return $this->belongsTo('App\Models\User');
   }
   public function officeNoteUser(){
      return $this->belongsTo('App\Models\User','office_note_updated_by');
   }
   public function startuser(){
      return $this->belongsTo('App\Models\User','start_user_id');
   }

   public function harekets()
   {
       return $this->transfer ? $this->transfer->harekets() : collect();
   }

}
