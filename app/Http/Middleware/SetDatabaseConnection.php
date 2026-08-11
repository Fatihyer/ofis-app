<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class SetDatabaseConnection
{
    public function handle($request, Closure $next)
    {
        $host = strtolower($request->getHost());

        // Varsayılan DB
        $database = 'ofis';

        switch ($host) {
            case 'ofis2025.parisvia.com':
                $database = 'ofis2025';
                break;
            case 'ofis.francevia.com':
                $database = 'ofis_francevia';
                break;    

            case 'ofis.parisvia.com':
            default:
                $database = 'ofis';
                break;
        }

        // SADECE database adını değiştiriyoruz
        Config::set('database.connections.mysql.database', $database);
        
       $host = strtolower($request->getHost());
Config::set('session.cookie', 'via_session_' . str_replace('.', '_', $host));
Config::set('session.domain', null);
Config::set('session.secure', $request->isSecure());
Config::set('session.same_site', 'lax');

        // SADECE mysql connection’ı sıfırla
        DB::purge('mysql');
        DB::reconnect('mysql');

        return $next($request);
    }
}
