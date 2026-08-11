<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AdminMiddleware
{
    public function handle($request, Closure $next)
    {
        $userCount = User::count();
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($userCount !== 1 && !$user->hasPermissionTo('Administer roles & permissions')) {
            $adminDriverUserRoutes = [
                'users.index',
                'users.create',
                'users.store',
                'users.edit',
                'users.update',
                'users.pass',
                'users.passwordupdate',
            ];

            if ($user->hasRole('Admin') && in_array(optional($request->route())->getName(), $adminDriverUserRoutes, true)) {
                return $next($request);
            }

            $uninvoicedRoutes = [
                'posts.uninvoiced',
                'posts.uninvoiced.bulk-exclude',
                'posts.uninvoiced.exclude',
                'posts.uninvoiced.restore',
            ];

            if ($user->hasPermissionTo('posts.uninvoiced') && in_array(optional($request->route())->getName(), $uninvoicedRoutes, true)) {
                return $next($request);
            }

            abort(403);
        }

        return $next($request);
    }
}
