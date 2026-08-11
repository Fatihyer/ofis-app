<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransferMessage extends Model 
{
    protected $fillable = [
        'user_id',
        'transfer_id',
        'body',
        'created_at',
        'updated_at'
    ];

    

    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }
}
  

