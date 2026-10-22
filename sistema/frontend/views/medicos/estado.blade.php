@extends('layouts.medicos')
@section('title', $medico->activo ? 'Desactivar médico' : 'Activar médico')
@section('doctor-title', $medico->activo ? 'Revisa antes de desactivar.' : 'Reactivar la ficha del médico.')
@section('doctor-description', 'El cambio conserva los datos, relaciones, horarios y citas existentes.')
@section('doctor-content')
@if($errors->has('version'))<div class="notice"><a href="{{ route('medicos.estado.edit', $medico) }}">Recargar la confirmación</a>.</div>@endif
<form class="admin-editor" method="POST" action="{{ route('medicos.estado.update', $medico) }}">
    @csrf @method('PATCH')
    <input type="hidden" name="version" value="{{ $medico->editVersion() }}"><input type="hidden" name="activo" value="{{ $medico->activo ? '0' : '1' }}">
    <section class="admin-panel"><h2>{{ $medico->nombres }} {{ $medico->apellidos }}</h2><p class="panel-description">{{ $medico->codigo }} · {{ $medico->registro_profesional }}</p><p class="panel-description">{{ $medico->activo ? 'La ficha quedará inactiva para futuras operaciones que requieran un médico activo.' : 'La ficha quedará activa. Debe tener al menos una especialidad activa asignada.' }} La cuenta de acceso conserva su propio estado y no se modifica.</p>@if($citasPendientes)<div class="notice">Tiene {{ $citasPendientes }} citas futuras pendientes. Se conservarán; coordina su atención con recepción. Esta acción no las cancela ni reprograma.</div>@endif</section>
    <div class="admin-form-footer"><a class="button secondary" href="{{ route('medicos.index') }}">Cancelar</a><button class="button primary" type="submit">{{ $medico->activo ? 'Confirmar desactivación' : 'Confirmar activación' }}</button></div>
</form>
@endsection
