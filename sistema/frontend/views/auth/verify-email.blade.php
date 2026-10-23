@extends('layouts.auth')
@section('title', 'Confirmar correo')
@section('form')
    <h2 id="form-title">Confirma tu correo.</h2>
    @if($localDelivery)
        <div class="notice" role="status">En este entorno de prueba los mensajes se guardan localmente: no llegarán a tu bandeja. La persona que administra el sistema debe habilitar el envío de correos.</div>
    @else
        <p class="form-description">Abre el enlace de verificación enviado al correo de tu cuenta. Revisa también spam o correo no deseado.</p>
    @endif
    <p class="form-description">El enlace vence en 60 minutos y debes usarlo con esta sesión iniciada.</p>
    <x-messages />
    <form method="POST" action="{{ route('verification.send') }}" class="auth-form" data-verification-resend data-retry-after="{{ $retryAfter }}">@csrf<button class="button primary" type="submit">{{ $localDelivery ? 'Generar enlace de prueba' : 'Solicitar nuevo enlace' }}</button><p class="form-description" data-verification-wait @if(!$retryAfter) hidden @endif>Podrás solicitar otro enlace en <span data-verification-seconds>{{ $retryAfter }}</span> segundos.</p></form>
    <p class="form-description">Una vez verificado el correo, podrás <a href="{{ route('citas.entry') }}">continuar con tu reserva</a>.</p>
    <a class="back-link" href="{{ route('inicio') }}">Volver a mi cuenta</a>
@endsection
