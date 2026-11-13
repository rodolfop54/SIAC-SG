<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IdleSession
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $last = $request->session()->get('last_activity', now()->timestamp);
            if (now()->timestamp - $last >= 900) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login', ['ended' => 1]);
            }
            $request->session()->put('last_activity', now()->timestamp);
        }

        return $next($request);
    }
}
