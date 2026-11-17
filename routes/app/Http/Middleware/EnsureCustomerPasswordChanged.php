<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerPasswordChanged
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user('customer')?->must_change_password) {
            return redirect()->route('portal.password.edit')->with('warning', 'For security, replace your temporary password before continuing.');
        }

        return $next($request);
    }
}
