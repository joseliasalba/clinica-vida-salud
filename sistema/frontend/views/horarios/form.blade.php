@extends('layouts.horarios')
@section('title', $registro->exists ? 'Editar '.$tipo : 'Registrar '.$tipo)
@section('schedule-title', $tipo === 'horario' ? 'Prepara la atención del médico.' : 'Reserva un periodo sin atención.')
@section('schedule-description', $tipo === 'horario' ? 'Puedes crear varias franjas para el mismo día, siempre que no se superpongan.' : 'Los bloqueos no cancelan citas: primero deben resolverse las reservas afectadas.')
@section('schedule-content')
@php
    $prefix = $tipo === 'horario' ? 'horarios' : 'horarios.bloqueos';
    $text = static fn (string $field, ?string $fallback = null): string => is_string(old($field, $fallback)) ? old($field, $fallback) : '';
    $dayValue = old('dia_semana', $registro->dia_semana ?? 1);
    $dayValue = is_scalar($dayValue) ? (string)$dayValue : '';
    $durationValue = old('duracion_turno_min', $registro->duracion_turno_min ?? 30);
    $durationValue = is_scalar($durationValue) ? (string)$durationValue : '';
@endphp
@if($errors->has('version'))<div class="notice"><a href="{{ route($prefix.'.edit', $registro) }}">Recargar el registro actualizado</a>. Conserva tus cambios antes de recargar.</div>@endif
<form class="admin-editor" method="POST" action="{{ $registro->exists ? route($prefix.'.update', $registro) : route($prefix.'.store') }}">
    @csrf
    @if($registro->exists)@method('PUT')<input type="hidden" name="version" value="{{ $text('version', $registro->editVersion()) }}">@endif
    <section class="admin-panel"><h2>Médico</h2><p class="panel-description">Las altas requieren un médico activo. El médico de un registro existente se conserva.</p><div class="field"><label for="medico_id">Médico de la agenda</label><select id="medico_id" name="medico_id" required @disabled($registro->exists) @error('medico_id') aria-invalid="true" aria-describedby="form-errors" @enderror><option value="">Selecciona un médico</option>@foreach($medicos as $medico)<option value="{{ $medico->id }}" @selected(is_scalar(old('medico_id', $registro->medico_id)) && (string)old('medico_id', $registro->medico_id) === (string)$medico->id)>{{ $medico->nombres }} {{ $medico->apellidos }} · {{ $medico->codigo }}{{ !$medico->activo ? ' (inactivo)' : '' }}</option>@endforeach</select>@if($registro->exists)<input type="hidden" name="medico_id" value="{{ $registro->medico_id }}">@endif</div>@if($medicos->isEmpty())<p class="panel-description">No hay médicos activos disponibles. Una cuenta con permiso de gestión de médicos debe registrar o activar uno primero.</p>@endif</section>
    @if($tipo === 'horario')
    <section class="admin-panel"><h2>Franja semanal</h2><p class="panel-description">Inicio y final deben estar dentro del mismo día. La duración debe dividir la franja en turnos completos, de 1 a 480 minutos.</p><div class="admin-form-grid">
        <div class="field"><label for="dia_semana">Día de atención</label><select id="dia_semana" name="dia_semana" required>@foreach(\App\Models\HorarioAtencion::DAYS as $value => $day)<option value="{{ $value }}" @selected($dayValue === (string)$value)>{{ $day }}</option>@endforeach</select></div>
        <div class="field"><label for="duracion_turno_min">Duración por turno (minutos)</label><input type="number" id="duracion_turno_min" name="duracion_turno_min" min="1" max="480" step="1" required value="{{ $durationValue }}" @error('duracion_turno_min') aria-invalid="true" aria-describedby="form-errors" @enderror></div>
        <div class="field"><label for="hora_inicio">Hora inicial</label><input type="time" id="hora_inicio" name="hora_inicio" required step="60" value="{{ $text('hora_inicio', $registro->hora_inicio ? substr($registro->hora_inicio, 0, 5) : '09:00') }}" @error('hora_inicio') aria-invalid="true" aria-describedby="form-errors" @enderror></div>
        <div class="field"><label for="hora_fin">Hora final</label><input type="time" id="hora_fin" name="hora_fin" required step="60" value="{{ $text('hora_fin', $registro->hora_fin ? substr($registro->hora_fin, 0, 5) : '12:00') }}" @error('hora_fin') aria-invalid="true" aria-describedby="form-errors" @enderror></div>
        <div class="field"><label for="vigente_desde">Vigente desde</label><input type="date" id="vigente_desde" name="vigente_desde" required value="{{ $text('vigente_desde', $registro->vigente_desde?->toDateString() ?? now()->toDateString()) }}" @error('vigente_desde') aria-invalid="true" aria-describedby="form-errors" @enderror></div>
        <div class="field"><label for="vigente_hasta">Vigente hasta (opcional)</label><input type="date" id="vigente_hasta" name="vigente_hasta" value="{{ $text('vigente_hasta', $registro->vigente_hasta?->toDateString()) }}" @error('vigente_hasta') aria-invalid="true" aria-describedby="form-errors" @enderror><small>El día final está incluido. Déjalo vacío para una vigencia abierta.</small></div>
    </div></section>
    @else
    <section class="admin-panel"><h2>Periodo sin atención</h2><div class="admin-form-grid"><div class="field"><label for="inicio">Fecha y hora inicial</label><input type="datetime-local" id="inicio" name="inicio" required step="60" value="{{ $text('inicio', $registro->inicio?->format('Y-m-d\TH:i') ?? now()->addDay()->setTime(8, 0)->format('Y-m-d\TH:i')) }}" @error('inicio') aria-invalid="true" aria-describedby="form-errors" @enderror></div><div class="field"><label for="fin">Fecha y hora final</label><input type="datetime-local" id="fin" name="fin" required step="60" value="{{ $text('fin', $registro->fin?->format('Y-m-d\TH:i') ?? now()->addDay()->setTime(18, 0)->format('Y-m-d\TH:i')) }}" @error('fin') aria-invalid="true" aria-describedby="form-errors" @enderror></div></div><div class="field"><label for="motivo">Motivo administrativo</label><textarea id="motivo" name="motivo" required maxlength="255" rows="3" @error('motivo') aria-invalid="true" aria-describedby="form-errors" @enderror>{{ $text('motivo', $registro->motivo) }}</textarea><small>Máximo 255 caracteres. No incluyas datos de pacientes.</small></div></section>
    @endif
    <section class="admin-panel"><h2>Conservación de la agenda</h2><p class="panel-description">El estado se modifica desde una confirmación separada en el listado. Si el horario ya tiene citas, su día, franja, duración y fecha inicial se conservan. La vigencia final debe respetar las citas pendientes. Los cambios no cancelan ni reprograman citas.</p></section>
    <div class="admin-form-footer"><a class="button secondary" href="{{ route($prefix.'.index') }}">Cancelar</a><button class="button primary" type="submit">{{ $registro->exists ? 'Guardar cambios' : 'Registrar '.$tipo }}</button></div>
</form>
@endsection
