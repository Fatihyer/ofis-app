<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcenteEmail extends Model
{
   
    protected $fillable = ['email', 'acente_id'];

    public function setEmailAttribute($value): void
    {
        $this->attributes['email'] = strtolower(trim((string) $value));
    }

    public function acente()
    {
        return $this->belongsTo('App\Models\Acente');
    }
}
