@extends('layouts.citas')
@section('title', 'Cancelar cita')
@section('citas-title', 'Cancelar una cita médica.')
@section('citas-description', 'Revisa los datos de la cita y registra el motivo antes de confirmar la cancelación.')

@section('citas-content')
@php
    $text = static fn (string $field, string $fallback = ''): string => is_string(old($field, $fallback)) ? old($field, $fallback) : '';
@endphp
@if($errors->has('version') || $errors->has('estado'))
    <div class="notice" role="alert"><a href="{{ route('citas.index') }}">Volver a la lista de citas</a> para revisar el estado actualizado antes de continuar.</div>
@endif

<form class="admin-editor" method="POST" action="{{ route('citas.cancelar.update', $cita) }}">
    @csrf
    @method('PATCH')
    <input type="hidden" name="version" value="{{ $text('version', $cita->editVersion()) }}">
    @error('version')<p class="notice" role="alert">{{ $message }}</p>@enderror
    @error('estado')<p class="notice" role="alert">{{ $message }}</p>@enderror
    <section class="admin-panel">
        <h2>Cita {{ $cita->codigo }}</h2>
        <dl class="citas-summary">
            <div><dt>Paciente</dt><dd>{{ $cita->paciente->nombres }} {{ $cita->paciente->apellidos }}</dd></div>
            <div><dt>Médico</dt><dd>{{ $cita->medico->nombres }} {{ $cita->medico->apellidos }}</dd></div>
            <div><dt>Especialidad</dt><dd>{{ $cita->especialidad->nombre }}</dd></div>
            <div><dt>Fecha y hora</dt><dd>{{ $cita->inicio->format('d/m/Y H:i') }} a {{ $cita->fin->format('H:i') }}</dd></div>
            <div><dt>Estado actual</dt><dd>{{ $cita->estadoLabel() }}</dd></div>
        </dl>
        <p class="panel-description">Al confirmar, la cita quedará cancelada y el horario estará disponible para una nueva reserva. Sus datos y el motivo quedarán registrados en el historial.</p>
    </section>
    <section class="admin-panel">
        <h2>Motivo de la cancelación</h2>
        <div class="field">
            <label for="motivo">Motivo *</label>
            <textarea id="motivo" name="motivo" rows="4" required maxlength="255" aria-describedby="motivo-help{{ $errors->has('motivo') ? ' motivo-error' : '' }}" @error('motivo') aria-invalid="true" @enderror>{{ $text('motivo') }}</textarea>
            <small id="motivo-help">Describe el motivo en un máximo de 255 caracteres.</small>
            @error('motivo')<small id="motivo-error" role="alert">{{ $message }}</small>@enderror
        </div>
        <div class="field">
            <label for="confirmar" class="choice inline"><input id="confirmar" name="confirmar" type="checkbox" value="1" required @checked(old('confirmar') === '1') aria-describedby="confirmar-help{{ $errors->has('confirmar') ? ' confirmar-error' : '' }}" @error('confirmar') aria-invalid="true" @enderror><span>Confirmo que deseo cancelar esta cita.</span></label>
            <small id="confirmar-help">La cancelación queda registrada al enviar este formulario.</small>
            @error('confirmar')<small id="confirmar-error" role="alert">{{ $message }}</small>@enderror
        </div>
    </section>
    <div class="admin-form-footer">
        <a class="button secondary" href="{{ route('citas.index') }}">Volver sin cancelar</a>
        <button class="button primary" type="submit">Confirmar cancelación</button>
    </div>
</form>
@endsection
