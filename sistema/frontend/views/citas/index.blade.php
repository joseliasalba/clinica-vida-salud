@extends('layouts.citas')
@section('title', 'Gestionar citas')
@section('citas-content')
<form class="admin-filters" method="GET" action="{{ route('citas.index') }}">
    <div class="field"><label for="q">Código o paciente</label><input id="q" name="q" type="search" maxlength="100" value="{{ is_string(request('q')) ? request('q') : '' }}"></div>
    <div class="field"><label for="fecha">Fecha</label><input id="fecha" type="date" name="fecha" value="{{ is_string(request('fecha')) ? request('fecha') : '' }}"></div>
    <div class="field"><label for="estado">Estado</label><select id="estado" name="estado"><option value="">Todos</option>@foreach(['programada'=>'Programada','confirmada'=>'Confirmada','en_curso'=>'En atención','completada'=>'Atendida','cancelada'=>'Cancelada','inasistencia'=>'Inasistencia'] as $key=>$label)<option value="{{ $key }}" @selected(request('estado')===$key)>{{ $label }}</option>@endforeach</select></div>
    <button class="button secondary">Buscar</button><a href="{{ route('citas.index') }}">Limpiar filtros</a>
</form>
<div class="admin-result-heading"><h2>Citas registradas</h2><span>{{ $citas->total() }} resultados</span></div>
@php($columns=['codigo'=>'Código','paciente'=>'Paciente','medico'=>'Médico y especialidad','fecha'=>'Fecha y hora','estado'=>'Estado','acciones'=>'Acciones'])
<x-responsive-table :columns="$columns" label="Citas registradas">
@forelse($citas as $cita)
<tr role="row">
    <x-table-cell column="codigo" heading>{{ $cita->codigo }}</x-table-cell>
    <x-table-cell column="paciente">{{ $cita->paciente->nombres }} {{ $cita->paciente->apellidos }}</x-table-cell>
    <x-table-cell column="medico">{{ $cita->medico->nombres }} {{ $cita->medico->apellidos }}<br>{{ $cita->especialidad->nombre }}</x-table-cell>
    <x-table-cell column="fecha">{{ $cita->inicio->format('d/m/Y H:i') }}–{{ $cita->fin->format('H:i') }}</x-table-cell>
    <x-table-cell column="estado"><span class="state cita-state-{{ $cita->estado }}">{{ $cita->estadoLabel() }}</span></x-table-cell>
    <x-table-cell column="acciones"><div class="citas-actions">
        @if($canManage)<a class="table-action" href="{{ route('pacientes.historial', $cita->paciente_id) }}">Historial</a>@endif
        @if($canManage && in_array($cita->estado, ['programada','confirmada'], true))
            <a class="table-action" href="{{ route('citas.reprogramar.edit', $cita) }}">Reprogramar</a><a class="table-action" href="{{ route('citas.cancelar.edit', $cita) }}">Cancelar</a>
        @endif
        @if(!empty(\App\Services\Citas\CitaWorkflow::TRANSITIONS[$cita->estado]))<a class="table-action" href="{{ route('citas.asistencia.edit', $cita) }}">Registrar estado</a>@else<span>Sin cambios disponibles</span>@endif
    </div></x-table-cell>
</tr>
@empty<x-table-empty>No hay citas que coincidan con estos filtros.</x-table-empty>@endforelse
</x-responsive-table>
<div class="admin-pagination">{{ $citas->links() }}</div>
@endsection
