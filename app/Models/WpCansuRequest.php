<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WpCansuRequest extends Model
{
    protected $connection = 'wordpress';
    protected $table = 'cansu_requests';
    public $timestamps = false; // Eğer tabloda created_at ve updated_at sütunları yoksa

    protected $fillable = [
        'created_at','lang','cansu_start','cansu_end','cansu_time','cansu_passengers',
        'retour','retour_datetime','car_sur_place','client_name','client_phone',
        'client_email','client_notes','email_status'
    ];

    protected $casts = [
        'created_at'      => 'datetime',
        'cansu_time'      => 'datetime',
        'retour_datetime' => 'datetime',
        'cansu_passengers'=> 'integer',
    ];
}   