@extends('layouts.auth')
@section('title', 'Iniciar sesión')
@section('form')
    <h2 id="form-title">Qué gusto verte.</h2>
    <p class="form-description">Ingresa a tu cuenta para continuar.</p>
    <x-messages />
    <form method="POST" action="{{ route('login') }}" class="auth-form">
        @csrf
        <div class="field"><label for="identificador">Usuario o correo electrónico</label><input id="identificador" name="identificador" value="{{ old('identificador') }}" required maxlength="255" autocomplete="username" autofocus @error('identificador') aria-invalid="true" aria-describedby="form-errors" @enderror></div>
        <div class="field"><label for="password">Contraseña</label><div class="password-field"><input id="password" type="password" name="password" required maxlength="1024" autocomplete="current-password" @error('password') aria-invalid="true" aria-describedby="form-errors" @enderror><button class="reveal" type="button" data-reveal="password" aria-controls="password" aria-pressed="false" hidden>Mostrar</button></div></div>
        <a class="forgot-link" href="{{ route('password.request') }}">Olvidé mi contraseña</a>
        <button class="button primary" type="submit">Iniciar sesión <span aria-hidden="true">↗</span></button>
    </form>
    <p class="registration-login">¿Es tu primera vez? <a href="{{ route('registro') }}">Crea tu cuenta de paciente</a></p>
    <a class="back-link" href="{{ route('portada') }}">← Volver a la clínica</a>
    <div class="private-note"><span aria-hidden="true">◇</span> Un acceso personal para cuidar tu información.</div>
@endsection
