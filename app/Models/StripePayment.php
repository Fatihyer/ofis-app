<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StripePayment extends Model
{
    protected $fillable = [
        'stripe_id',
        'stripe_account',
        'payment_intent_id',
        'sirket_id',
        'acente_id',
        'post_id',
        'amount',
        'currency',
        'status',
        'customer_email',
        'description',
        'paid_at',
        'raw',
        'bank_import_id',
        'offset_id',
    ];

    protected $casts = [
        'raw' => 'array',
        'paid_at' => 'datetime',
    ];

    public function acente()
    {
        return $this->belongsTo(Acente::class);
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function bankImport()
    {
        return $this->belongsTo(BankImport::class, 'bank_import_id');
    }

    public function offset()
    {
        return $this->belongsTo(Offset::class);
    }
}
