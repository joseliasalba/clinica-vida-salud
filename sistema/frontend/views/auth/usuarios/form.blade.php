@extends('layouts.admin')
@section('title', $usuario->exists ? 'Editar usuario' : 'Crear usuario')
@section('admin-title', $usuario->exists ? 'Una cuenta, el acceso adecuado.' : 'Demos la bienvenida a una nueva cuenta.')
@section('admin-description', $usuario->exists ? 'Actualiza los datos y revisa las funciones que podrá utilizar esta persona.' : 'Completa los datos y asigna al menos un rol activo.')
@section('admin-content')
@php
    $selected = session()->hasOldInput() ? old('roles', []) : $selectedRoles;
    $selected = is_array($selected) ? $selected : [];
    $self = $usuario->exists && $usuario->id === auth()->id();
    $text = static fn (string $field, ?string $fallback = null): string => is_string(old($field, $fallback)) ? old($field, $fallback) : '';
@endphp
<form class="admin-editor" method="POST" action="{{ $usuario->exists ? route('usuarios.update', $usuario) : route('usuarios.store') }}">
    @csrf
    @if($usuario->exists) @method('PUT') @endif
    <section class="admin-panel"><h2>Datos de la cuenta</h2><p class="panel-description">El usuario y el correo deben ser únicos. El nombre de usuario no admite espacios ni @.</p><div class="admin-form-grid">
        <div class="field"><label for="nombre_usuario">Nombre de usuario</label><input id="nombre_usuario" name="nombre_usuario" required maxlength="50" value="{{ $text('nombre_usuario', $usuario->nombre_usuario) }}" autocomplete="off" @error('nombre_usuario') aria-invalid="true" aria-describedby="form-errors" @enderror></div>
        <div class="field"><label for="correo">Correo electrónico</label><input id="correo" name="correo" type="email" required maxlength="255" value="{{ $text('correo', $usuario->correo) }}" autocomplete="off" @error('correo') aria-invalid="true" aria-describedby="form-errors" @enderror></div>
        <div class="field"><label for="password">{{ $usuario->exists ? 'Nueva contraseña (opcional)' : 'Contraseña inicial' }}</label><div class="password-field"><input id="password" name="password" type="password" minlength="8" maxlength="128" autocomplete="new-password" data-password-policy @required(!$usuario->exists) aria-describedby="password-help" @error('password') aria-invalid="true" @enderror><button class="reveal" type="button" data-reveal="password" aria-controls="password" aria-pressed="false" hidden>Mostrar</button></div><small id="password-help">De 8 a 128 caracteres. Evita contraseñas comunes.@if($usuario->exists) Deja ambos campos vacíos para conservarla.@endif</small><small data-password-feedback aria-live="polite"></small></div>
        <div class="field"><label for="password_confirmation">Confirmar contraseña</label><input id="password_confirmation" name="password_confirmation" type="password" minlength="8" maxlength="128" autocomplete="new-password" @required(!$usuario->exists)></div>
    </div></section>
    <section class="admin-panel"><h2>Roles y funciones</h2><p class="panel-description">Puedes asignar varios roles. Quitar un rol retira sus funciones; seleccionar una asignación vencida la renueva sin fecha de vencimiento.</p><fieldset class="choice-grid"><legend class="sr-only">Selecciona los roles del usuario</legend>
        @forelse($roles as $role)
            <label class="choice {{ !$role->activo ? 'disabled' : '' }}"><input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked($role->activo && in_array($role->id, $selected)) @disabled(!$role->activo)><span><strong>{{ $role->nombre }}</strong><small>{{ $role->descripcion ?: ($role->activo ? 'Rol disponible para asignación.' : 'Rol desactivado; no se puede asignar.') }}</small></span></label>
        @empty<p class="panel-description">No hay roles disponibles. Crea o activa un rol antes de guardar la cuenta.</p>@endforelse
    </fieldset></section>
    <section class="admin-panel"><h2>Estado de acceso</h2><input type="hidden" name="activo" value="{{ $self ? '1' : '0' }}"><label class="choice inline"><input type="checkbox" name="activo" value="1" @checked(old('activo', $usuario->exists ? $usuario->activo : true)) @disabled($self)><span><strong>Cuenta activa</strong><small>{{ $self ? 'Tu propia cuenta no se puede desactivar.' : 'Al desactivarla, la persona no podrá ingresar y sus sesiones anteriores perderán acceso.' }}</small></span></label></section>
    <div class="admin-form-footer"><a class="button secondary" href="{{ route('usuarios.index') }}">Cancelar</a><button class="button primary" type="submit">{{ $usuario->exists ? 'Guardar cambios' : 'Crear usuario' }}</button></div>
</form>
@endsection
