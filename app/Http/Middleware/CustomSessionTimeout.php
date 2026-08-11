<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Config;

class CustomSessionTimeout
{
    public function handle($request, Closure $next)
    {
        if (auth()->check()) {
            // Kullanıcı giriş yapmışsa, oturum süresini 1 ay (43200 dakika) olarak ayarla
            Config::set('session.lifetime', 43200); // 30 gün = 43200 dakika
        } else {
            // Giriş yapmamış kullanıcılar için varsayılan süre
            Config::set('session.lifetime', 120); // 2 saat
        }

        return $next($request);
    }
}
