@extends('layouts.historial')
@section('title', 'Historial de citas')
@section('historial-content')
<form class="admin-filters" method="GET" action="{{ route('historial.index') }}">
    @if($canSelectPatient)
        <div class="field"><label for="q">Buscar paciente por código, CI o nombre</label><input type="search" id="q" name="q" maxlength="100" value="{{ $filters['q'] ?? '' }}" aria-describedby="patient-search-help"><small id="patient-search-help">Escribe un dato y pulsa Consultar para actualizar las opciones de pacientes.</small></div>
        <div class="field"><label for="paciente_id">Paciente</label><select id="paciente_id" name="paciente_id" aria-describedby="patient-selection-help"><option value="">Selecciona un paciente</option>@foreach($pacientes as $option)<option value="{{ $option->id }}" @selected($paciente?->id === $option->id)>{{ $option->apellidos }}, {{ $option->nombres }} · {{ $option->codigo }}{{ $option->ci ? ' · CI '.$option->ci : '' }}{{ $option->activo ? '' : ' · Ficha inactiva' }}</option>@endforeach</select><small id="patient-selection-help">{{ $hasMorePatients ? 'Hay más de 25 coincidencias. Precisa la búsqueda para encontrar al paciente.' : 'Incluye fichas activas e inactivas.' }}</small></div>
    @endif
    <div class="field"><label for="desde">Desde</label><input type="date" id="desde" name="desde" value="{{ $filters['desde'] ?? '' }}" @if(!empty($filters['hasta'])) max="{{ $filters['hasta'] }}" @endif></div>
    <div class="field"><label for="hasta">Hasta</label><input type="date" id="hasta" name="hasta" value="{{ $filters['hasta'] ?? '' }}" @if(!empty($filters['desde'])) min="{{ $filters['desde'] }}" @endif></div>
    <div class="field"><label for="estado">Estado de la cita</label><select id="estado" name="estado"><option value="">Todos los estados</option>@foreach($estados as $value => $label)<option value="{{ $value }}" @selected(($filters['estado'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
    <button class="button secondary" type="submit">Consultar</button><a class="filter-reset" href="{{ route('historial.index', $canSelectPatient && $paciente ? ['paciente_id' => $paciente->id] : []) }}">Limpiar filtros</a>
</form>
@if($paciente)
    <div class="notice" role="status"><strong>{{ $canSelectPatient ? 'Paciente: ' : 'Tu historial: ' }}{{ $paciente->nombres }} {{ $paciente->apellidos }}</strong><span> · {{ $paciente->codigo }}{{ $paciente->activo ? '' : ' · Ficha inactiva' }}</span></div>
@endif
<div class="admin-result-heading"><h2>Citas registradas</h2><span>{{ $citas->total() }} {{ $citas->total() === 1 ? 'cita' : 'citas' }}</span></div>
<x-responsive-table label="Historial de citas del paciente" :columns="['codigo' => 'Código', 'fecha' => 'Fecha y hora', 'medico' => 'Médico', 'especialidad' => 'Especialidad', 'estado' => 'Estado', 'registro' => 'Fecha de registro']">
@forelse($citas as $cita)
    <tr role="row">
        <x-table-cell column="codigo" heading>{{ $cita->codigo }}</x-table-cell>
        <x-table-cell column="fecha"><time datetime="{{ $cita->inicio->format('Y-m-d\TH:i:s') }}">{{ $cita->inicio->format('d/m/Y H:i') }}</time>–{{ $cita->fin->format('H:i') }}</x-table-cell>
        <x-table-cell column="medico">{{ $cita->medico->nombres }} {{ $cita->medico->apellidos }}</x-table-cell>
        <x-table-cell column="especialidad">{{ $cita->especialidad->nombre }}</x-table-cell>
        <x-table-cell column="estado"><span class="state cita-state-{{ $cita->estado }}">{{ $cita->estadoLabel() }}</span></x-table-cell>
        <x-table-cell column="registro"><time datetime="{{ $cita->created_at->format('Y-m-d\TH:i:s') }}">{{ $cita->created_at->format('d/m/Y H:i') }}</time></x-table-cell>
    </tr>
@empty
    <x-table-empty><div class="admin-empty"><h3>{{ ! $paciente && $canSelectPatient ? 'Selecciona un paciente para consultar sus citas.' : 'No hay citas para mostrar.' }}</h3><p>{{ ! $paciente && ! $canSelectPatient ? 'Tu cuenta todavía no tiene una ficha de paciente vinculada.' : ($paciente ? 'Prueba con otras fechas o estados. El historial también incluye citas canceladas.' : 'Busca por código, CI o nombre y elige la ficha del paciente.') }}</p></div></x-table-empty>
@endforelse
</x-responsive-table>
<div class="admin-pagination">{{ $citas->links() }}</div>
@endsection
