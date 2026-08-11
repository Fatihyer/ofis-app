<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankImport extends Model
{
    use HasFactory;

    protected $table = 'fuel_bank_imports';

    protected $fillable = [
        'sirket_id',
        'external_source',
        'external_company',
        'external_id',
        'acente_id',
        'date',
        'operation',
        'debit',
        'credit',
        'currency',
        'value_date',
        'interbank_label',
        'offset_id',
    ];

    public function acente()
    {
        return $this->belongsTo(Acente::class);
    }
}
