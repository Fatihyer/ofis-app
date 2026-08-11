<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        'api/*', // Tüm API rotalarını CSRF'den muaf tutar
       'invoices/*',
       'invoices',
       'invoices/{id}',
       'invoices/{id}/edit',
       'invoices/{id}/*',
       'invoices/{id}/*',
       '/webhook',
        '/fallback',
        '/status_callback',
        '/twilio/whatsapp/webhook',
        '/stripe/webhook',
        'stripe/webhook',
        'stripe/*/webhook',
      
    ];
}
