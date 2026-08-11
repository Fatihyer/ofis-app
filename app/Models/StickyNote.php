<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StickyNote extends Model
{
    use HasFactory;
    

    protected $fillable = ['content', 'color', 'order', 'user_id'];

    // app/Models/StickyNote.php
            public function user()
            {
                return $this->belongsTo(User::class);
            }
}
