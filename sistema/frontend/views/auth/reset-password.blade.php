@extends('layouts.auth')
@section('title', 'Nueva contraseña')
@section('form')
    <h2 id="form-title">Un nuevo comienzo.</h2>
    <p class="form-description">Elige una frase de contraseña que solo tú conozcas.</p>
    @include('auth.messages')
    <form method="POST" action="{{ route('password.update') }}" class="auth-form">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="field"><label for="correo">Correo electrónico</label><input id="correo" type="email" name="correo" value="{{ old('correo', request('correo')) }}" required maxlength="255" autocomplete="email"></div>
        <div class="field"><label for="password">Nueva contraseña</label><div class="password-field"><input id="password" type="password" name="password" required minlength="15" maxlength="128" autocomplete="new-password" aria-describedby="password-help" data-password-policy><button class="reveal" type="button" data-reveal="password" aria-controls="password" aria-pressed="false" hidden>Mostrar</button></div><small id="password-help">Entre 15 y 128 caracteres. Puedes usar espacios y acentos. Evita claves comunes.</small><small data-password-feedback aria-live="polite"></small></div>
        <div class="field"><label for="password_confirmation">Confirma tu nueva contraseña</label><input id="password_confirmation" type="password" name="password_confirmation" required minlength="15" maxlength="128" autocomplete="new-password"></div>
        <button class="button primary" type="submit">Guardar nueva contraseña <span aria-hidden="true">↗</span></button>
    </form>
    <a class="back-link" href="{{ route('password.request') }}">Solicitar otro enlace</a>
@endsection
