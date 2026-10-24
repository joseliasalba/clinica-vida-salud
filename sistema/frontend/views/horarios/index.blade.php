@extends('layouts.horarios')
@section('title', 'Horarios de atención')
@section('schedule-title', 'Un tiempo para cada atención.')
@section('schedule-description', 'Configura las franjas semanales, su vigencia y la duración de cada turno.')
@section('schedule-content')
<form class="admin-filters" method="GET" action="{{ route('horarios.index') }}">
    <div class="field"><label for="medico_id">Médico</label><select id="medico_id" name="medico_id"><option value="">Todos</option>@foreach($medicos as $medico)<option value="{{ $medico->id }}" @selected((string)request('medico_id') === (string)$medico->id)>{{ $medico->nombres }} {{ $medico->apellidos }}</option>@endforeach</select></div>
    <div class="field"><label for="dia_semana">Día</label><select id="dia_semana" name="dia_semana"><option value="">Todos</option>@foreach(\App\Models\HorarioAtencion::DAYS as $key => $day)<option value="{{ $key }}" @selected((string)request('dia_semana') === (string)$key)>{{ $day }}</option>@endforeach</select></div>
    <div class="field"><label for="estado">Estado</label><select id="estado" name="estado"><option value="">Todos</option><option value="activo" @selected(request('estado') === 'activo')>Activos</option><option value="inactivo" @selected(request('estado') === 'inactivo')>Inactivos</option></select></div><button class="button secondary" type="submit">Buscar</button><a class="filter-reset" href="{{ route('horarios.index') }}">Limpiar</a>
</form>
<div class="admin-result-heading"><h2>Franjas de atención</h2><span>{{ $horarios->total() }} registros</span></div>
<x-responsive-table label="Lista de horarios" :columns="['medico' => 'Médico', 'dia' => 'Día', 'franja' => 'Franja', 'turno' => 'Duración del turno', 'vigencia' => 'Vigencia', 'estado' => 'Estado', 'acciones' => 'Acciones']">
@forelse($horarios as $horario)<tr role="row">
    <x-table-cell column="medico" heading>{{ $horario->medico->nombres }} {{ $horario->medico->apellidos }}<small class="cell-note">{{ $horario->medico->codigo }}{{ !$horario->medico->activo ? ' · Médico inactivo' : '' }}</small></x-table-cell>
    <x-table-cell column="dia">{{ \App\Models\HorarioAtencion::DAYS[$horario->dia_semana] }}</x-table-cell>
    <x-table-cell column="franja">{{ substr($horario->hora_inicio, 0, 5) }} – {{ substr($horario->hora_fin, 0, 5) }}</x-table-cell>
    <x-table-cell column="turno">{{ $horario->duracion_turno_min }} minutos</x-table-cell>
    <x-table-cell column="vigencia">Desde {{ $horario->vigente_desde->format('d/m/Y') }}<small class="cell-note">{{ $horario->vigente_hasta ? 'Hasta '.$horario->vigente_hasta->format('d/m/Y') : 'Sin fecha final' }}</small></x-table-cell>
    <x-table-cell column="estado"><span class="state {{ $horario->activo ? 'active' : 'inactive' }}">{{ $horario->activo ? 'Activo' : 'Inactivo' }}</span></x-table-cell>
    <x-table-cell column="acciones"><div class="record-actions"><a class="table-action" href="{{ route('horarios.edit', $horario) }}">Editar</a><a class="table-action" href="{{ route('horarios.estado.edit', $horario) }}">{{ $horario->activo ? 'Desactivar' : 'Activar' }}</a></div></x-table-cell>
</tr>@empty<x-table-empty><div class="admin-empty"><h3>No encontramos horarios con estos filtros.</h3><p>Registra un médico y sus franjas de atención para preparar la disponibilidad.</p><a class="button primary" href="{{ route('horarios.create') }}">Registrar horario</a></div></x-table-empty>@endforelse
</x-responsive-table><div class="admin-pagination">{{ $horarios->links() }}</div>
@endsection
