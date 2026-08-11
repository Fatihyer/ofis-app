<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TalepMailAttachment extends Model
{
    protected $table = 'talep_mail_attachments';

    protected $fillable = [
        'talep_maili_id',
        'acente_id',
        'original_name',
        'stored_path',
        'mime_type',
        'size_bytes',
        'sha256',
        'parsed_text',
        'parsed_json',
        'parse_status',
        'parse_error',
        'ocr_used',
    ];

    protected $casts = [
        'parsed_json' => 'array',
        'ocr_used' => 'boolean',
    ];

    public function mail()
    {
        return $this->belongsTo(TalepMaili::class, 'talep_maili_id');
    }

    public function acente()
    {
        return $this->belongsTo(Acente::class, 'acente_id');
    }
}
