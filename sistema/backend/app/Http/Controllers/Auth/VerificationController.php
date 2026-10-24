<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Throwable;

class VerificationController extends Controller
{
    public function notice(Request $request): View|RedirectResponse
    {
        return $request->user()->hasVerifiedEmail() ? redirect()->route('inicio') : view('auth.verify-email', [
            'localDelivery' => in_array(config('mail.default'), ['log', 'array'], true),
            'retryAfter' => RateLimiter::availableIn('verification-resend:'.$request->user()->id),
        ]);
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        return redirect()->route('inicio')->with('status', 'Tu correo fue verificado correctamente.');
    }

    public function send(Request $request): RedirectResponse|JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('inicio');
        }
        $key = 'verification-resend:'.$request->user()->id;
        if (RateLimiter::hit($key, 60) > 1) {
            $seconds = max(1, RateLimiter::availableIn($key));
            $message = 'Espera '.$seconds.' segundos antes de solicitar otro enlace de verificación.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 429)->header('Retry-After', $seconds)
                : redirect()->route('verification.notice')->withErrors(['correo' => $message])->header('Retry-After', $seconds);
        }
        try {
            $request->user()->sendEmailVerificationNotification();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('verification.notice')->withErrors(['correo' => 'No pudimos enviar el correo. Intenta nuevamente más tarde.']);
        }

        $status = in_array(config('mail.default'), ['log', 'array'], true)
            ? 'El mensaje se generó en modo de prueba y no se envió a tu bandeja. Falta habilitar el envío de correos.'
            : 'Solicitamos un nuevo correo de verificación. Revisa también la carpeta de spam.';

        return redirect()->route('verification.notice')->with('status', $status);
    }
}
