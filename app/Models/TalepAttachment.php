<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class TalepAttachment extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'talepler_attachments';
    use HasFactory;

    protected $fillable = [
        'talep_id', 
        'dosya_adi', 
        'orijinal_adi'
    ];

    public function talep()
    {
        return $this->belongsTo(Talep::class);
    }
}
