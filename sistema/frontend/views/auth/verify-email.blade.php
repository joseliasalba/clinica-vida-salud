@extends('layouts.auth')
@section('title', 'Confirmar correo')
@section('form')
    <h2 id="form-title">Confirma tu correo.</h2><p class="form-description">Abre el enlace de verificación que enviamos al correo de tu cuenta. El enlace vence en 60 minutos y debes usarlo con esta sesión iniciada.</p>
    <x-messages />
    <form method="POST" action="{{ route('verification.send') }}" class="auth-form">@csrf<button class="button primary" type="submit">Solicitar nuevo enlace</button></form>
    <p class="form-description">Una vez verificado el correo, podrás <a href="{{ route('citas.entry') }}">continuar con tu reserva</a>.</p>
    <a class="back-link" href="{{ route('inicio') }}">Volver a mi cuenta</a>
@endsection
