@extends('layouts.especialidades')
@section('title', $especialidad->exists ? 'Editar especialidad' : 'Registrar especialidad')
@section('specialty-title', $especialidad->exists ? 'Mantén el catálogo actualizado.' : 'Una nueva especialidad para la clínica.')
@section('specialty-description', $especialidad->exists ? 'Actualiza el nombre y la descripción de la especialidad.' : 'Comprueba que la especialidad no exista antes de registrarla.')

@section('specialty-content')
@php
    $text = static fn (string $field, ?string $fallback = null): string => is_string(old($field, $fallback)) ? old($field, $fallback) : '';
@endphp
@if($especialidad->exists && $errors->has('version'))
    <div class="notice"><a href="{{ route('especialidades.edit', $especialidad) }}">Recargar la especialidad actualizada</a>. Conserva tus cambios antes de recargar el formulario.</div>
@endif

<form class="admin-editor" method="POST" action="{{ $especialidad->exists ? route('especialidades.update', $especialidad) : route('especialidades.store') }}">
    @csrf
    @if($especialidad->exists)
        @method('PUT')
        <input type="hidden" name="version" value="{{ $text('version', $especialidad->editVersion()) }}">
    @endif
    <section class="admin-panel">
        <h2>Datos de la especialidad</h2>
        <p class="panel-description">El nombre es obligatorio y debe ser único, incluso entre las especialidades inactivas.</p>
        <div class="admin-form-grid">
            <div class="field specialty-wide">
                <label for="nombre">Nombre de la especialidad *</label>
                <input id="nombre" name="nombre" required maxlength="255" value="{{ $text('nombre', $especialidad->nombre) }}" aria-describedby="nombre-help{{ $errors->has('nombre') ? ' nombre-error' : '' }}" @error('nombre') aria-invalid="true" @enderror>
                <small id="nombre-help">Por ejemplo, Cardiología o Medicina General.</small>
                @error('nombre')<small class="specialty-field-error" id="nombre-error">{{ $message }}</small>@enderror
            </div>
            <div class="field specialty-wide">
                <label for="descripcion">Descripción (opcional)</label>
                <textarea id="descripcion" name="descripcion" rows="4" maxlength="255" aria-describedby="descripcion-help{{ $errors->has('descripcion') ? ' descripcion-error' : '' }}" @error('descripcion') aria-invalid="true" @enderror>{{ $text('descripcion', $especialidad->descripcion) }}</textarea>
                <small id="descripcion-help">Describe brevemente la especialidad. Máximo 255 caracteres.</small>
                @error('descripcion')<small class="specialty-field-error" id="descripcion-error">{{ $message }}</small>@enderror
            </div>
        </div>
    </section>
    <section class="admin-panel">
        <h2>Estado de la especialidad</h2>
        @if($especialidad->exists)
            <p class="panel-description">Estado actual: <span class="state {{ $especialidad->activo ? 'active' : 'inactive' }}">{{ $especialidad->activo ? 'Activa' : 'Inactiva' }}</span>. Para cambiarlo, guarda primero tus cambios y selecciona Activar o Desactivar en el listado.</p>
        @else
            <p class="panel-description">La especialidad se registrará activa. Podrás desactivarla desde el listado conservando sus datos.</p>
        @endif
    </section>
    <div class="admin-form-footer">
        <a class="button secondary" href="{{ route('especialidades.index') }}">Cancelar</a>
        <button class="button primary" type="submit">{{ $especialidad->exists ? 'Guardar cambios' : 'Registrar especialidad' }}</button>
    </div>
</form>
@endsection
