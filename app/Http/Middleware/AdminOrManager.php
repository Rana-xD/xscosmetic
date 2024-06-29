<?php

namespace App\Http\Middleware;

use Closure;

class AdminOrManager
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
        if(auth()->user()->isAdmin() || auth()->user()->isManager() ) {
            return $next($request);
        }
        return redirect('/');
    }
}
