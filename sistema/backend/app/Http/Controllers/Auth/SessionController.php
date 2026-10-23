<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class SessionController extends Controller
{
    public function store(LoginRequest $request): RedirectResponse
    {
        $key = $request->throttleKey();
        if (RateLimiter::tooManyAttempts($key, config('seguridad.intentos_login'))) {
            throw ValidationException::withMessages(['identificador' => 'Demasiados intentos. Intenta nuevamente en unos minutos.']);
        }

        $identifier = $request->string('identificador')->trim()->toString();
        $matches = User::query()->where('activo', true)
            ->where(function ($query) use ($identifier): void {
                $query->where('nombre_usuario', $identifier)->orWhereRaw('lower(correo) = ?', [mb_strtolower($identifier)]);
            })->limit(2)->pluck('id');

        if (! Auth::attempt(['id' => $matches->count() === 1 ? $matches->first() : 0, 'password' => $request->input('password')])) {
            RateLimiter::hit($key, config('seguridad.espera_login'));
            Log::notice('auth.login_failed', ['ip' => $request->ip()]);
            throw ValidationException::withMessages(['identificador' => 'Credenciales incorrectas.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->session()->forget('url.intended');
        $request->session()->put('auth_last_activity', now()->timestamp);
        $request->session()->put('auth_password_hash', $request->user()->getAuthPassword());
        $request->session()->put('auth_session_token', $request->user()->remember_token ?? '');
        Log::info('auth.login', ['usuario_id' => Auth::id()]);

        return redirect()->route($request->session()->pull('booking.continue', false) ? 'citas.index' : 'inicio');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Log::info('auth.logout', ['usuario_id' => Auth::id()]);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Cerraste tu sesión correctamente.');
    }
}
