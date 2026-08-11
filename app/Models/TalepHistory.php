<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TalepHistory extends Model
{
    use HasFactory;

    protected $table = 'talep_histories';

    protected $fillable = [
        'talep_id',
        'user_id',
        'action_type',
        'field_name',
        'old_value',
        'new_value',
        'note',
    ];

    public function talep()
    {
        return $this->belongsTo(Talep::class, 'talep_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}