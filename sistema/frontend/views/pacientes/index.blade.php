@extends('layouts.pacientes')
@section('title', 'Pacientes')
@section('patient-title', 'Cada paciente, una atención cercana.')
@section('patient-description', 'Busca una ficha antes de registrar un paciente y mantén sus datos de contacto actualizados.')
@section('patient-content')
<form class="admin-filters" method="GET" action="{{ route('pacientes.index') }}">
    <div class="field"><label for="q">Buscar por código, CI o nombre</label><input type="search" id="q" name="q" maxlength="100" value="{{ request('q') }}" placeholder="Escribe los datos del paciente"></div>
    <div class="field"><label for="estado">Estado de la ficha</label><select id="estado" name="estado"><option value="">Todos</option><option value="activo" @selected(request('estado') === 'activo')>Activos</option><option value="inactivo" @selected(request('estado') === 'inactivo')>Inactivos</option></select></div>
    <button class="button secondary" type="submit">Buscar</button><a class="filter-reset" href="{{ route('pacientes.index') }}">Limpiar</a>
</form>
<div class="admin-result-heading"><h2>Fichas de pacientes</h2><span>{{ $pacientes->total() }} {{ $pacientes->total() === 1 ? 'paciente' : 'pacientes' }}</span></div>
<x-responsive-table label="Lista de pacientes" :columns="['paciente' => 'Paciente', 'ci' => 'CI', 'telefono' => 'Teléfono', 'estado' => 'Estado', 'acciones' => 'Acciones']">
@forelse($pacientes as $paciente)
    <tr role="row">
        <x-table-cell column="paciente" heading><span class="user-label">{{ $paciente->nombres }} {{ $paciente->apellidos }}</span><small class="cell-note">{{ $paciente->codigo }}</small></x-table-cell>
        <x-table-cell column="ci">{{ $paciente->ci ?: 'Sin CI' }}</x-table-cell>
        <x-table-cell column="telefono">{{ $paciente->telefono }}</x-table-cell>
        <x-table-cell column="estado"><span class="state {{ $paciente->activo ? 'active' : 'inactive' }}">{{ $paciente->activo ? 'Activo' : 'Inactivo' }}</span></x-table-cell>
        <x-table-cell column="acciones"><div class="record-actions"><a class="table-action" href="{{ route('pacientes.show', $paciente) }}" aria-label="Ver ficha de {{ $paciente->nombres }} {{ $paciente->apellidos }}">Ver ficha ↗</a><a class="table-action" href="{{ route('pacientes.edit', $paciente) }}" aria-label="Editar ficha de {{ $paciente->nombres }} {{ $paciente->apellidos }}">Editar</a></div></x-table-cell>
    </tr>
@empty
    <x-table-empty><div class="admin-empty"><h3>No encontramos pacientes con estos filtros.</h3><p>Prueba con otro nombre o CI. Si aún no existe su ficha, puedes registrar al paciente.</p><a class="button primary" href="{{ route('pacientes.create') }}">Registrar paciente</a></div></x-table-empty>
@endforelse
</x-responsive-table>
<div class="admin-pagination">{{ $pacientes->links() }}</div>
@endsection
