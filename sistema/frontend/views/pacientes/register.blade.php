@extends('layouts.app')
@section('title', 'Crear cuenta de paciente')
@section('header-actions')<a class="button secondary" href="{{ route('login') }}">Ya tengo cuenta</a>@endsection
@section('content')
<div class="registration-shell">
    <aside class="registration-intro"><a class="back-link" href="{{ route('portada') }}">← Volver a la clínica</a><span class="eyebrow">TU PRIMER PASO</span><h1>Un acceso personal<br>para cuidar de ti.</h1><p>Completa tus datos tal como figuran en tu documento. Crearemos tu cuenta y tu ficha administrativa de paciente.</p><div class="notice"><strong>¿Ya fuiste atendido?</strong><p>Si recepción ya registró tu ficha, contacta a la clínica para revisar la vinculación. No crees otra ficha para la misma persona.</p></div><p>El registro no genera una cita ni una historia clínica. La reserva en línea estará disponible próximamente.</p></aside>
    <section class="registration-card" aria-labelledby="register-title"><span class="eyebrow">CREAR CUENTA</span><h2 id="register-title">Te damos la bienvenida.</h2><p class="form-description">Los campos indicados como opcionales pueden quedar vacíos.</p><x-messages />
        <form method="POST" action="{{ route('registro.store') }}" class="registration-form">
            @csrf
            <fieldset><legend>1. Tus datos personales</legend><div class="registration-fields">
                @foreach([
                    ['nombres', 'Nombres', 'text', 'given-name', 150, true],
                    ['apellidos', 'Apellidos', 'text', 'family-name', 150, true],
                    ['ci', 'CI y complemento (opcional)', 'text', 'off', 50, false],
                    ['fecha_nacimiento', 'Fecha de nacimiento', 'date', 'bday', null, true],
                    ['telefono', 'Teléfono', 'tel', 'tel', 50, true],
                    ['sexo', 'Sexo (opcional)', 'text', 'sex', 30, false],
                    ['direccion', 'Dirección (opcional)', 'text', 'street-address', 255, false],
                ] as [$name, $label, $type, $autocomplete, $maxlength, $required])
                    <div class="field"><label for="{{ $name }}">{{ $label }}</label><input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" autocomplete="{{ $autocomplete }}" value="{{ old($name) }}" @required($required) @if($maxlength) maxlength="{{ $maxlength }}" @endif @if($type === 'date') max="{{ now()->toDateString() }}" @endif @error($name) aria-invalid="true" aria-describedby="form-errors" @enderror></div>
                @endforeach
            </div></fieldset>
            <fieldset><legend>2. Tu acceso al portal</legend><div class="registration-fields">
                <div class="field"><label for="correo">Correo electrónico</label><input id="correo" name="correo" type="email" autocomplete="email" value="{{ old('correo') }}" required maxlength="255" @error('correo') aria-invalid="true" aria-describedby="form-errors" @enderror></div>
                <div class="field"><label for="nombre_usuario">Nombre de usuario</label><input id="nombre_usuario" name="nombre_usuario" autocomplete="username" value="{{ old('nombre_usuario') }}" required maxlength="50" aria-describedby="username-help @error('nombre_usuario') form-errors @enderror" @error('nombre_usuario') aria-invalid="true" @enderror><small id="username-help">Sin espacios ni @. Puedes usar letras, números, punto y guion.</small></div>
                <div class="field"><label for="password">Contraseña</label><div class="password-field"><input id="password" name="password" type="password" required minlength="8" maxlength="128" autocomplete="new-password" data-password-policy aria-describedby="password-help @error('password') form-errors @enderror" @error('password') aria-invalid="true" @enderror><button class="reveal" type="button" data-reveal="password" aria-controls="password" aria-pressed="false" hidden>Mostrar</button></div><small id="password-help">Entre 8 y 128 caracteres. Evita claves comunes.</small><small data-password-feedback aria-live="polite"></small></div>
                <div class="field"><label for="password_confirmation">Repetir contraseña</label><input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" maxlength="128" autocomplete="new-password"></div>
            </div></fieldset>
            <p class="registration-note">Usaremos tu correo para confirmar la cuenta y recuperar el acceso. La cuenta se crea exclusivamente con el rol paciente.</p>
            <button class="button primary" type="submit">Crear mi cuenta <span aria-hidden="true">↗</span></button>
            <p class="registration-login">¿Ya tienes cuenta? <a href="{{ route('login') }}">Inicia sesión</a></p>
        </form>
    </section>
</div>
@endsection
