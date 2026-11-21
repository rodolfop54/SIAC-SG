<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', $request->is('recuperar/*') ? 'no-referrer' : 'same-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        if ($request->user() || $request->is('bot*', 'registro*', 'acceso*', 'recuperar*')) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}
