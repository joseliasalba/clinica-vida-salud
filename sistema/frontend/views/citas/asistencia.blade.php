@extends('layouts.citas')
@section('title', 'Confirmar atención o inasistencia')
@section('citas-title', 'Confirma el estado de la cita.')
@section('citas-description', 'Revisa el paciente, la fecha y el resultado que quedará registrado en el historial.')

@section('citas-content')
@php
    $version = old('version', $cita->editVersion());
    $version = is_string($version) ? $version : '';
    $selected = old('estado', '');
    $motivo = old('motivo', '');
    $motivo = is_string($motivo) ? $motivo : '';
@endphp
@if($errors->has('version'))
    <div class="notice"><a href="{{ route('citas.asistencia.edit', $cita) }}">Recargar la cita actualizada</a> y revisar su estado antes de confirmar.</div>
@endif

<form class="admin-editor" method="POST" action="{{ route('citas.asistencia.update', $cita) }}">
    @csrf
    @method('PATCH')
    <input type="hidden" name="version" value="{{ $version }}">
    <section class="admin-panel">
        <h2>Cita {{ $cita->codigo }}</h2>
        <dl class="citas-summary">
            <div><dt>Paciente</dt><dd>{{ $cita->paciente->nombres }} {{ $cita->paciente->apellidos }}</dd></div>
            <div><dt>Especialidad</dt><dd>{{ $cita->especialidad->nombre }}</dd></div>
            <div><dt>Fecha y hora de Bolivia</dt><dd>{{ $cita->inicio->timezone('America/La_Paz')->format('d/m/Y H:i') }} a {{ $cita->fin->timezone('America/La_Paz')->format('H:i') }}</dd></div>
            <div><dt>Estado actual</dt><dd><span class="state">{{ $cita->estadoLabel() }}</span></dd></div>
        </dl>
    </section>
    <section class="admin-panel">
        <h2>Resultado de la cita</h2>
        @if($estados === [])
            <p class="notice" role="status">Esta cita no admite cambios de atención en este momento. Las citas atendidas, canceladas o con inasistencia conservan su estado.</p>
        @else
            <div class="admin-form-grid"><div class="field">
                <label for="estado">Nuevo estado <span aria-hidden="true">*</span></label>
                <select name="estado" id="estado" required aria-describedby="estado-help" @if($errors->has('estado')) aria-invalid="true" @endif>
                    <option value="">Selecciona un estado</option>
                    @foreach($estados as $value => $label)
                        <option value="{{ $value }}" @selected($selected === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <small id="estado-help">Confirmada indica que se confirmó la cita. Atendida registra que la atención terminó. Inasistencia indica que el paciente no asistió. Atendida e inasistencia se habilitan desde la hora de inicio y cierran la cita.</small>
            </div>
            <div class="field">
                <label for="motivo">Observación (opcional)</label>
                <textarea id="motivo" name="motivo" rows="3" maxlength="255" aria-describedby="motivo-help" @if($errors->has('motivo')) aria-invalid="true" @endif>{{ $motivo }}</textarea>
                <small id="motivo-help">Máximo 255 caracteres. La observación queda junto al usuario y al cambio de estado en el historial.</small>
            </div></div>
            <label class="choice inline" for="confirmacion">
                <input type="checkbox" id="confirmacion" name="confirmacion" value="1" required @checked(old('confirmacion') === '1')>
                <span>Revisé la cita y confirmo el cambio de estado seleccionado.</span>
            </label>
        @endif
    </section>
    <div class="admin-form-footer">
        <a class="button secondary" href="{{ route('citas.index') }}">Volver a citas</a>
        @if($estados !== [])<button class="button primary" type="submit">Confirmar cambio de estado</button>@endif
    </div>
</form>
@endsection
