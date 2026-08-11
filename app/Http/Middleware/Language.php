<?php

namespace App\Http\Middleware;

use Closure;
use Session;
use Carbon\Carbon;

class Language
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        
      if (Session::has('locale'))
      {
          $locale=Session::get('locale');
       
          
      }
        else {
            $locale="en";
        }
      \App::setLocale($locale);
      Carbon::setLocale($locale);
      return $next($request);
    }
}
