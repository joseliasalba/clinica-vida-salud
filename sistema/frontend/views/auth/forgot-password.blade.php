@extends('layouts.auth')
@section('title', 'Recuperar contraseña')
@section('form')
    <h2 id="form-title">Recupera tu acceso.</h2>
    <p class="form-description">Escribe el correo de tu cuenta. Te enviaremos un enlace para elegir una nueva contraseña.</p>
    @include('auth.messages')
    <form method="POST" action="{{ route('password.email') }}" class="auth-form">
        @csrf
        <div class="field"><label for="correo">Correo electrónico</label><input id="correo" type="email" name="correo" value="{{ old('correo') }}" required maxlength="255" autocomplete="email" autofocus @error('correo') aria-invalid="true" aria-describedby="form-errors" @enderror></div>
        <button class="button primary" type="submit">Enviar enlace <span aria-hidden="true">↗</span></button>
    </form>
    <a class="back-link" href="{{ route('login') }}">← Volver a iniciar sesión</a>
@endsection
