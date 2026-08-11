<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trajet extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'trajets';
    protected $casts = [
        'datetime' => 'datetime',
    ];
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'transfer_id',
        'type',
        'from',
        'google_address',
        'datetime',
        'order',
    ];

    /**
     * Get the transfer associated with the trajet.
     */
    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }
}
