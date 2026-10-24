@extends('layouts.gestion-citas')
@section('title', 'Reprogramar cita')
@section('citas-title', 'Reprogramar cita')
@section('citas-description', 'Selecciona un nuevo turno disponible. El paciente y la especialidad se conservan.')

@section('citas-content')
@php
    $text = static fn (string $field, string $fallback = ''): string => is_string(old($field, $fallback)) ? old($field, $fallback) : '';
    $eligible = in_array($cita->estado, ['programada', 'confirmada'], true);
@endphp
<section class="admin-panel">
    <h2>Cita {{ $cita->codigo }}</h2>
    <dl class="citas-summary">
        <div><dt>Paciente</dt><dd>{{ $cita->paciente->nombres }} {{ $cita->paciente->apellidos }}</dd></div>
        <div><dt>Especialidad</dt><dd>{{ $cita->especialidad->nombre }}</dd></div>
        <div><dt>Turno actual</dt><dd>{{ $cita->inicio->format('d/m/Y H:i') }} – {{ $cita->fin->format('H:i') }} (Bolivia)</dd></div>
        <div><dt>Estado</dt><dd>{{ ucfirst(str_replace('_', ' ', $cita->estado)) }}</dd></div>
    </dl>
</section>
@if($errors->has('version'))
    <div class="notice"><a href="{{ route('citas.reprogramar.edit', $cita) }}">Recargar la cita actualizada</a>. Revisa sus datos antes de confirmar otro cambio.</div>
@endif
@if(!$eligible)
    <div class="notice" role="status">Solo se pueden reprogramar citas programadas o confirmadas.</div>
    <a class="button secondary" href="{{ route('citas.gestion') }}">Volver a citas</a>
@else
<section class="admin-panel">
    <h2>Buscar un nuevo turno</h2>
    <p class="panel-description">Las horas corresponden a Bolivia. Solo se muestran turnos futuros de médicos activos de la misma especialidad.</p>
    <form method="GET" action="{{ route('citas.reprogramar.edit', $cita) }}" class="admin-form-grid">
        <div class="field">
            <label for="medico_id">Médico *</label>
            <select id="medico_id" name="medico_id" required>
                <option value="">Selecciona un médico</option>
                @foreach($medicos as $medico)
                    <option value="{{ $medico->id }}" @selected($medicoId === (int) $medico->id)>{{ $medico->apellidos }}, {{ $medico->nombres }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="fecha">Nueva fecha *</label>
            <input id="fecha" type="date" name="fecha" required value="{{ $fecha }}" min="{{ now('America/La_Paz')->format('Y-m-d') }}">
        </div>
        <div class="admin-form-footer"><button class="button secondary" type="submit">Consultar turnos</button></div>
    </form>
</section>
<form method="POST" action="{{ route('citas.reprogramar.update', $cita) }}" class="admin-editor">
    @csrf
    @method('PUT')
    <input type="hidden" name="version" value="{{ $text('version', $cita->editVersion()) }}">
    <input type="hidden" name="medico_id" value="{{ $medicoId }}">
    <input type="hidden" name="fecha" value="{{ $fecha }}">
    <section class="admin-panel">
        <h2>Turnos del {{ \Carbon\CarbonImmutable::parse($fecha)->format('d/m/Y') }}</h2>
        @if($turnos === [])
            <p class="notice" role="status">No hay turnos disponibles para este médico y fecha. Consulta otra fecha o selecciona otro médico.</p>
        @else
            <fieldset class="citas-slots">
                <legend>Selecciona el nuevo turno *</legend>
                @foreach($turnos as $turno)
                    @php($hora = substr($turno['inicio'], 11, 5))
                    <label class="citas-slot" for="turno-{{ $loop->index }}">
                        <input id="turno-{{ $loop->index }}" type="radio" name="turno" value="{{ $turno['horario_id'] }}|{{ $hora }}" required @checked($text('inicio') === $hora && (string) old('horario_id') === (string) $turno['horario_id'])>
                        <span>{{ $hora }} – {{ substr($turno['fin'], 11, 5) }} <small>({{ $turno['duracion_minutos'] }} minutos)</small></span>
                    </label>
                @endforeach
            </fieldset>
        @endif
        <div class="field">
            <label for="motivo">Motivo de reprogramación (opcional)</label>
            <textarea id="motivo" name="motivo" maxlength="255" rows="3" @error('motivo') aria-invalid="true" aria-describedby="motivo-error" @enderror>{{ $text('motivo') }}</textarea>
            @error('motivo')<small id="motivo-error">{{ $message }}</small>@enderror
        </div>
        <p class="panel-description">La disponibilidad se vuelve a comprobar al confirmar. Si el turno se ocupa, la cita conserva su reserva anterior.</p>
    </section>
    <div class="admin-form-footer">
        <a class="button secondary" href="{{ route('citas.gestion') }}">Volver a citas</a>
        <button class="button primary" type="submit" @disabled($turnos === [])>Confirmar reprogramación</button>
    </div>
</form>
@endif
@endsection
