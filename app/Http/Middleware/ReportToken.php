<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ReportToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('X-REPORT-TOKEN') ?? $request->query('token');
        if (!$token || $token !== config('services.report.token')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        return $next($request);
    }
}
