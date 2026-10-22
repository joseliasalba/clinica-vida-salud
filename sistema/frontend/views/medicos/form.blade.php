@extends('layouts.medicos')
@section('title', $medico->exists ? 'Editar médico' : 'Registrar médico')
@section('doctor-title', $medico->exists ? 'Mantén su información al día.' : 'Demos la bienvenida al equipo.')
@section('doctor-description', 'Completa la ficha administrativa y selecciona al menos una especialidad activa.')
@section('doctor-content')
@php
    $text = static fn (string $field, ?string $fallback = null): string => is_string(old($field, $fallback)) ? old($field, $fallback) : '';
    $selected = session()->hasOldInput() ? old('especialidades', []) : $seleccionadas;
    $selected = is_array($selected) ? $selected : [];
    $account = old('usuario_id', $medico->usuario_id);
@endphp
@if($medico->exists && $errors->has('version'))<div class="notice"><a href="{{ route('medicos.edit', $medico) }}">Recargar la ficha actualizada</a>. Conserva tus cambios antes de recargar.</div>@endif
<form class="admin-editor" method="POST" action="{{ $medico->exists ? route('medicos.update', $medico) : route('medicos.store') }}">
    @csrf
    @if($medico->exists)@method('PUT')<input type="hidden" name="version" value="{{ $text('version', $medico->editVersion()) }}">@endif
    <section class="admin-panel"><h2>Datos profesionales y contacto</h2><p class="panel-description">El registro profesional debe ser único. El código se asigna automáticamente; el correo de contacto no crea una cuenta.</p><div class="admin-form-grid">
        @foreach(['nombres' => ['Nombres', 'text', 150, 'given-name'], 'apellidos' => ['Apellidos', 'text', 150, 'family-name'], 'registro_profesional' => ['Registro profesional', 'text', 100, 'off'], 'telefono' => ['Teléfono', 'tel', 50, 'tel'], 'correo' => ['Correo de contacto', 'email', 255, 'email']] as $name => [$label, $type, $maximum, $autocomplete])
            <div class="field"><label for="{{ $name }}">{{ $label }}</label><input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" maxlength="{{ $maximum }}" required autocomplete="{{ $autocomplete }}" value="{{ $text($name, $medico->$name) }}" @error($name) aria-invalid="true" aria-describedby="form-errors" @enderror></div>
        @endforeach
    </div></section>
    <section class="admin-panel"><h2>Especialidades</h2><p class="panel-description">Selecciona una o varias. Las especialidades inactivas conservan su relación histórica y no admiten nuevas asignaciones.</p><fieldset class="choice-grid"><legend class="sr-only">Especialidades del médico</legend>
        @forelse($especialidades as $especialidad)<label class="choice {{ !$especialidad->activo ? 'disabled' : '' }}"><input type="checkbox" name="especialidades[]" value="{{ $especialidad->id }}" @checked(in_array($especialidad->id, $selected)) @disabled(!$especialidad->activo)><span><strong>{{ $especialidad->nombre }}</strong><small>{{ $especialidad->activo ? 'Disponible para asignar.' : 'Especialidad inactiva.' }}</small></span></label>@empty<p class="panel-description">Primero debes registrar una especialidad. <a href="{{ route('especialidades.create') }}">Registrar especialidad</a></p>@endforelse
    </fieldset></section>
    <section class="admin-panel"><h2>Cuenta de acceso (opcional)</h2><p class="panel-description">Vincula una cuenta existente con permiso de agenda propia. Esta operación no crea usuarios ni cambia roles, contraseñas o permisos. La agenda se implementará en CU-11.</p><div class="field"><label for="usuario_id">Cuenta vinculada al médico</label><select id="usuario_id" name="usuario_id" @error('usuario_id') aria-invalid="true" aria-describedby="form-errors" @enderror><option value="">Sin cuenta vinculada</option>@foreach($cuentas as $cuenta)<option value="{{ $cuenta->id }}" @selected(is_scalar($account) && (string)$account === (string)$cuenta->id)>{{ $cuenta->nombre_usuario }} · {{ $cuenta->correo }}{{ !$cuenta->activo ? ' (cuenta inactiva actual)' : '' }}</option>@endforeach</select></div></section>
    <section class="admin-panel"><h2>Estado de la ficha</h2><p class="panel-description">{{ $medico->exists ? 'El estado se cambia desde Activar o Desactivar en el listado, mediante una confirmación separada.' : 'El médico se registrará activo. Podrás desactivarlo sin eliminar su información.' }}</p></section>
    <div class="admin-form-footer"><a class="button secondary" href="{{ route('medicos.index') }}">Cancelar</a><button class="button primary" type="submit">{{ $medico->exists ? 'Guardar cambios' : 'Registrar médico' }}</button></div>
</form>
@endsection
