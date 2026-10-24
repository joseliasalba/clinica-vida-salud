<?php

namespace App\Http\Controllers\Pacientes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pacientes\RegisterPatientRequest;
use App\Services\Pacientes\PatientSelfRegistration;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;

class RegistrationController extends Controller
{
    public function create(): View
    {
        return view('pacientes.register');
    }

    public function store(RegisterPatientRequest $request, PatientSelfRegistration $registration): RedirectResponse
    {
        $user = $registration->register($request->validated());
        try {
            $user->sendEmailVerificationNotification();
            $status = 'Tu cuenta fue creada. Inicia sesión y confirma tu correo con el enlace de verificación. Crear la cuenta no reserva una cita.';
            if (in_array(config('mail.default'), ['log', 'array'], true)) {
                $status = 'Tu cuenta fue creada. El correo se generó en modo de prueba y no llegará a tu bandeja hasta habilitar el envío de correos. Inicia sesión para continuar; crear la cuenta no reserva una cita.';
            }
        } catch (Throwable $exception) {
            report($exception);
            $status = 'Tu cuenta fue creada, pero no pudimos enviar el correo. Inicia sesión para solicitar un nuevo enlace de verificación.';
        }

        return redirect()->route('login')->with('status', $status);
    }
}
