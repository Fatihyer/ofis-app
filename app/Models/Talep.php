<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Talep extends Model
{
    use HasFactory;

    protected $table = 'talepler';

    protected $fillable = [
        'request_no',
        'user_id',
        'acente_id',
        'talep_tarihi',
        'talep_kanali',
        'country',
        'service_type',
        'customer_name',
        'customer_phone',
        'customer_email',
        'total_pax',
        'vehicle_type',
        'pickup_location',
        'dropoff_location',
        'verilen_fiyat',
        'confirmed_price',
        'currency',
        'system_total',
        'discount_price',
        'second_discount_price',
        'discount_valid_until',
        'ai_suggested_total',
        'ai_suggested_at',
        'ai_model_version',
        'final_total',
        'admin_price_user_id',
        'admin_price_updated_at',
        'comment_admin',
        'relance_yapildi',
        'followup_date',
        'konfirme_durumu',
        'uzun_mesaj',
        'internal_notes',
        'message_id',
        'is_manual_override',
        'override_note',
        'converted_transfer_id',
        'confirmed_at',
        'vehicule_id',
        'service_type_id',
        'depot_id',
    ];

    protected $casts = [
        'talep_tarihi' => 'datetime',
        'followup_date' => 'datetime',
        'confirmed_at' => 'datetime',
        'admin_price_updated_at' => 'datetime',
        'relance_yapildi' => 'boolean',
        'is_manual_override' => 'boolean',
        'verilen_fiyat' => 'decimal:2',
        'confirmed_price' => 'decimal:2',
        'system_total' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'second_discount_price' => 'decimal:2',
        'discount_valid_until' => 'date',
        'ai_suggested_total' => 'decimal:2',
        'ai_suggested_at' => 'datetime',
        'final_total' => 'decimal:2',
        'total_pax' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function acente()
    {
        return $this->belongsTo(Acente::class, 'acente_id');
    }

    public function adminPriceUser()
    {
        return $this->belongsTo(User::class, 'admin_price_user_id');
    }

    public function attachments()
    {
        return $this->hasMany(TalepAttachment::class, 'talep_id');
    }

    public function days()
    {
        return $this->hasMany(TalepDay::class, 'talep_id')->orderBy('day_number');
    }

    public function quote()
    {
        return $this->hasOne(TalepQuote::class, 'talep_id');
    }

    public function histories()
    {
        return $this->hasMany(TalepHistory::class, 'talep_id')->latest();
    }

    public function mailler()
    {
        return $this->hasMany(TalepMaili::class, 'talep_id')
            ->orderByDesc('received_at')
            ->orderByDesc('id');
    }
    public function vehicule()
{
    return $this->belongsTo(Vehicule::class, 'vehicule_id');
}

public function depot()
{
    return $this->belongsTo(Depot::class, 'depot_id');
}



public function servicetype()
{
    return $this->belongsTo(Servicetype::class, 'service_type_id');
}

public function convertedTransfer()
{
    return $this->belongsTo(Transfer::class, 'converted_transfer_id');
}
}
