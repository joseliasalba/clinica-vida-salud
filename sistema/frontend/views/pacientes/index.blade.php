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
<div class="admin-table-wrap" role="region" aria-label="Lista de pacientes" tabindex="0"><table class="admin-table"><thead><tr><th scope="col">Paciente</th><th scope="col">CI</th><th scope="col">Teléfono</th><th scope="col">Estado</th><th scope="col">Acciones</th></tr></thead><tbody>
@forelse($pacientes as $paciente)
    <tr><th scope="row"><span class="user-label">{{ $paciente->nombres }} {{ $paciente->apellidos }}</span><small class="cell-note">{{ $paciente->codigo }}</small></th><td>{{ $paciente->ci ?: 'Sin CI' }}</td><td>{{ $paciente->telefono }}</td><td><span class="state {{ $paciente->activo ? 'active' : 'inactive' }}">{{ $paciente->activo ? 'Activo' : 'Inactivo' }}</span></td><td><div class="patient-row-actions"><a class="table-action" href="{{ route('pacientes.show', $paciente) }}" aria-label="Ver ficha de {{ $paciente->nombres }} {{ $paciente->apellidos }}">Ver ficha ↗</a><a class="table-action" href="{{ route('pacientes.edit', $paciente) }}" aria-label="Editar ficha de {{ $paciente->nombres }} {{ $paciente->apellidos }}">Editar</a></div></td></tr>
@empty
    <tr><td colspan="5"><div class="admin-empty"><h3>No encontramos pacientes con estos filtros.</h3><p>Prueba con otro nombre o CI. Si aún no existe su ficha, puedes registrar al paciente.</p><a class="button primary" href="{{ route('pacientes.create') }}">Registrar paciente</a></div></td></tr>
@endforelse
</tbody></table></div>
<div class="admin-pagination">{{ $pacientes->links() }}</div>
@endsection
