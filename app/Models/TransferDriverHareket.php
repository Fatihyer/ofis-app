<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransferDriverHareket extends Model
{
    protected $table = 'transfer_driver_harekets';

    protected $fillable = [
        'transfer_id',
        'driver_id',
        'driver_role',
        'hareket_id',
        'created_by',
        'updated_by',
    ];

    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }

    public function driver()
    {
        return $this->belongsTo(Acente::class, 'driver_id');
    }

    public function hareket()
    {
        return $this->belongsTo(Hareket::class);
    }
}
