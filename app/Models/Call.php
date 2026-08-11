<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Call extends Model
{


    protected $fillable = [
        'to', 'from', 'status', 'call_sid'
    ];
}