<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Jobs\SendPasswordResetLink;
use App\Models\User;
use App\Notifications\PasswordChanged;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(Request $request, string $token): View|RedirectResponse
    {
        $email = $request->query('correo');
        $user = is_string($email) && strlen($email) <= 255
            ? User::query()->where('activo', true)->whereRaw('lower(correo) = ?', [mb_strtolower($email)])->first()
            : null;
        if (! $user || strlen($token) > 255 || ! Password::tokenExists($user, $token)) {
            return redirect()->route('password.request')->with('status', 'El enlace no es válido o ha vencido. Solicita uno nuevo.');
        }

        return view('auth.reset-password', ['token' => $token]);
    }

    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate(['correo' => ['required', 'email', 'max:255']], ['correo.required' => 'Ingresa tu correo.', 'correo.email' => 'Ingresa un correo válido.']);
        SendPasswordResetLink::dispatch(mb_strtolower($data['correo']));

        return back()->with('status', 'Si el correo corresponde a una cuenta activa, recibirás un enlace para restablecer tu contraseña.');
    }

    public function reset(ResetPasswordRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $status = DB::transaction(function () use ($data): string {
            $user = User::query()->whereRaw('lower(correo) = ?', [mb_strtolower($data['correo'])])->where('activo', true)->lockForUpdate()->first();
            if (! $user) {
                return Password::INVALID_TOKEN;
            }

            return Password::reset([
                'correo' => $user->correo, 'password' => $data['password'], 'token' => $data['token'],
            ], function (User $user, string $password): void {
                $user->forceFill(['contrasena_hash' => $password, 'remember_token' => Str::random(60)])->save();
                event(new PasswordReset($user));
                $user->notify((new PasswordChanged)->afterCommit());
            });
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['correo' => 'El enlace no es válido o ha vencido. Solicita uno nuevo.']);
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Contraseña actualizada. Ingresa con tu nueva contraseña.');
    }
}
