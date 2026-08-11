<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendWhatsAppGroupMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can('whatsapp-groups.reply');
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:2', 'max:4000'],
            'idempotency_key' => ['nullable', 'string', 'max:120'],
        ];
    }
}
