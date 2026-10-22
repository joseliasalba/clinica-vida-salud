@extends('layouts.pacientes')
@section('title', $paciente->exists ? 'Editar paciente' : 'Registrar paciente')
@section('patient-title', $paciente->exists ? 'Mantengamos su ficha al día.' : 'Una ficha para empezar a cuidarte.')
@section('patient-description', $paciente->exists ? 'Revisa los datos administrativos y de contacto del paciente.' : 'Busca primero si ya existe una ficha. El registro no necesita una cuenta de acceso.')
@section('patient-content')
@php
    $text = static fn (string $field, ?string $fallback = null): string => is_string(old($field, $fallback)) ? old($field, $fallback) : '';
@endphp
@if($errors->has('version'))<div class="notice"><a href="{{ route('pacientes.edit', $paciente) }}">Recargar la ficha actualizada</a>. Conserva tus notas antes de recargar el formulario.</div>@endif
<form class="admin-editor" method="POST" action="{{ $paciente->exists ? route('pacientes.update', $paciente) : route('pacientes.store') }}">
    @csrf
    @if($paciente->exists) @method('PUT')<input type="hidden" name="version" value="{{ $text('version', $paciente->editVersion()) }}"> @endif
    <section class="admin-panel"><h2>Identificación del paciente</h2><p class="panel-description">Los campos marcados con * son obligatorios. El CI es opcional; cada paciente recibe un código único.@if($paciente->exists) Código: <strong>{{ $paciente->codigo }}</strong>.@endif</p><div class="admin-form-grid">
        <div class="field"><label for="ci">CI (opcional)</label><input id="ci" name="ci" maxlength="50" value="{{ $text('ci', $paciente->ci) }}" aria-describedby="ci-help" @error('ci') aria-invalid="true" @enderror><small id="ci-help">Incluye su complemento si corresponde. Si no tiene CI, déjalo vacío; no uses el de otra persona.</small></div>
        <div class="field"><label for="fecha_nacimiento">Fecha de nacimiento *</label><input id="fecha_nacimiento" name="fecha_nacimiento" type="date" required max="{{ today()->toDateString() }}" value="{{ $text('fecha_nacimiento', $paciente->fecha_nacimiento?->format('Y-m-d')) }}" @error('fecha_nacimiento') aria-invalid="true" @enderror></div>
        <div class="field"><label for="nombres">Nombres *</label><input id="nombres" name="nombres" required maxlength="150" value="{{ $text('nombres', $paciente->nombres) }}" @error('nombres') aria-invalid="true" @enderror></div>
        <div class="field"><label for="apellidos">Apellidos *</label><input id="apellidos" name="apellidos" required maxlength="150" value="{{ $text('apellidos', $paciente->apellidos) }}" @error('apellidos') aria-invalid="true" @enderror></div>
        <div class="field"><label for="sexo">Sexo (opcional)</label><input id="sexo" name="sexo" maxlength="30" value="{{ $text('sexo', $paciente->sexo) }}"></div>
    </div></section>
    <section class="admin-panel"><h2>Datos de contacto</h2><div class="admin-form-grid">
        <div class="field"><label for="telefono">Teléfono *</label><input id="telefono" name="telefono" type="tel" required maxlength="50" value="{{ $text('telefono', $paciente->telefono) }}" @error('telefono') aria-invalid="true" @enderror><small>Puede ser un teléfono de contacto familiar. Incluye el prefijo si corresponde.</small></div>
        <div class="field"><label for="correo">Correo (opcional)</label><input id="correo" name="correo" type="email" maxlength="255" value="{{ $text('correo', $paciente->correo) }}" @error('correo') aria-invalid="true" @enderror></div>
        <div class="field patient-wide"><label for="direccion">Dirección (opcional)</label><input id="direccion" name="direccion" maxlength="255" value="{{ $text('direccion', $paciente->direccion) }}"></div>
    </div></section>
    <section class="admin-panel"><h2>Estado de la ficha</h2><input type="hidden" name="activo" value="0"><label class="choice inline"><input type="checkbox" name="activo" value="1" @checked(old('activo', $paciente->exists ? $paciente->activo : true))><span><strong>Paciente activo</strong><small>Desactivar conserva la ficha y sus referencias. El estado de una cuenta de acceso vinculada no cambia.</small></span></label></section>
    <div class="admin-form-footer"><a class="button secondary" href="{{ $paciente->exists ? route('pacientes.show', $paciente) : route('pacientes.index') }}">Cancelar</a><button class="button primary" type="submit">{{ $paciente->exists ? 'Guardar cambios' : 'Registrar paciente' }}</button></div>
</form>
@endsection
