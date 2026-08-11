<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HareketFile extends Model
{
    protected $table = 'hareket_files';

    protected $fillable = [
        'hareket_id',
        'original_name',
        'path',
        'mime',
        'size',
    ];

    public function hareket()
    {
        return $this->belongsTo(Hareket::class, 'hareket_id');
    }
}
