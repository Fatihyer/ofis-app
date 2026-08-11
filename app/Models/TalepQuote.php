<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TalepQuote extends Model
{
    use HasFactory;

    protected $table = 'talep_quotes';

    protected $fillable = [
        'talep_id',
        'subtotal',
        'extras_total',
        'margin_percent',
        'margin_amount',
        'vat_rate',
        'vat_amount',
        'system_total',
        'final_total',
        'currency',
        'is_manual_override',
        'override_note',
        'calculation_json',
        'calculated_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'extras_total' => 'decimal:2',
        'margin_percent' => 'decimal:2',
        'margin_amount' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'system_total' => 'decimal:2',
        'final_total' => 'decimal:2',
        'is_manual_override' => 'boolean',
        'calculation_json' => 'array',
        'calculated_at' => 'datetime',
    ];

    public function talep()
    {
        return $this->belongsTo(Talep::class, 'talep_id');
    }
}