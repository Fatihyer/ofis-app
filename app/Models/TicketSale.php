<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketSale extends Model
{
    protected $fillable = [
        'whatsapp_group_id', 'whatsapp_group_message_id', 'sale_date', 'product',
        'qty_adult', 'qty_child', 'unit_price_adult', 'unit_price_child', 'amount',
        'customer_raw', 'customer_key', 'acente_id', 'source_line', 'source_hash',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'amount' => 'decimal:2',
        'unit_price_adult' => 'decimal:2',
        'unit_price_child' => 'decimal:2',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(WhatsAppGroup::class, 'whatsapp_group_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(WhatsAppGroupMessage::class, 'whatsapp_group_message_id');
    }

    public function acente(): BelongsTo
    {
        return $this->belongsTo(Acente::class, 'acente_id');
    }

    public function getQtyTotalAttribute(): int
    {
        return (int) $this->qty_adult + (int) $this->qty_child;
    }
}
