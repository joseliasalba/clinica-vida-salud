<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ProtectSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user()?->fresh();
        $last = $request->session()->get('auth_last_activity', 0);
        $hash = $request->session()->get('auth_password_hash', '');

        if (! $user || ! $user->activo || now()->timestamp - $last >= config('seguridad.inactividad')
            || ! is_string($hash) || ! hash_equals($user->getAuthPassword(), $hash)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('status', 'Tu sesión finalizó. Ingresa nuevamente.');
        }

        $request->session()->put('auth_last_activity', now()->timestamp);

        return $next($request);
    }
}
