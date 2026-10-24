@extends('layouts.medicos')
@section('title', 'Médicos')
@section('doctor-title', 'Un equipo para cuidar de ti.')
@section('doctor-description', 'Organiza los datos profesionales y las especialidades de cada médico.')
@section('doctor-content')
<form class="admin-filters" method="GET" action="{{ route('medicos.index') }}">
    <div class="field"><label for="q">Buscar por código, registro o nombre</label><input type="search" id="q" name="q" maxlength="100" value="{{ is_string(request('q')) ? request('q') : '' }}" placeholder="Escribe los datos del médico"></div>
    <div class="field"><label for="especialidad">Especialidad</label><select id="especialidad" name="especialidad"><option value="">Todas</option>@foreach($especialidades as $especialidad)<option value="{{ $especialidad->id }}" @selected((string)request('especialidad') === (string)$especialidad->id)>{{ $especialidad->nombre }}{{ !$especialidad->activo ? ' (inactiva)' : '' }}</option>@endforeach</select></div>
    <div class="field"><label for="estado">Estado de la ficha</label><select id="estado" name="estado"><option value="">Todos</option><option value="activo" @selected(request('estado') === 'activo')>Activos</option><option value="inactivo" @selected(request('estado') === 'inactivo')>Inactivos</option></select></div>
    <button class="button secondary" type="submit">Buscar</button><a class="filter-reset" href="{{ route('medicos.index') }}">Limpiar</a>
</form>
<div class="admin-result-heading"><h2>Equipo médico</h2><span>{{ $medicos->total() }} {{ $medicos->total() === 1 ? 'médico' : 'médicos' }}</span></div>
<x-responsive-table label="Lista de médicos" :columns="['medico' => 'Médico', 'registro' => 'Registro profesional', 'especialidades' => 'Especialidades', 'contacto' => 'Contacto', 'cuenta' => 'Cuenta de acceso', 'estado' => 'Estado', 'acciones' => 'Acciones']">
@forelse($medicos as $medico)
    <tr role="row">
        <x-table-cell column="medico" heading><span class="user-label">{{ $medico->nombres }} {{ $medico->apellidos }}</span><small class="cell-note">{{ $medico->codigo }}</small></x-table-cell>
        <x-table-cell column="registro">{{ $medico->registro_profesional }}</x-table-cell>
        <x-table-cell column="especialidades">@forelse($medico->especialidades->filter(fn ($item) => $item->pivot->activo) as $especialidad)<span class="cell-note">{{ $especialidad->nombre }}{{ !$especialidad->activo ? ' (inactiva)' : '' }}</span>@empty Sin asignaciones vigentes @endforelse</x-table-cell>
        <x-table-cell column="contacto">{{ $medico->telefono }}<small class="cell-note">{{ $medico->correo }}</small></x-table-cell>
        <x-table-cell column="cuenta">{{ $medico->cuenta?->nombre_usuario ?? 'Sin cuenta vinculada' }}</x-table-cell>
        <x-table-cell column="estado"><span class="state {{ $medico->activo ? 'active' : 'inactive' }}">{{ $medico->activo ? 'Activo' : 'Inactivo' }}</span></x-table-cell>
        <x-table-cell column="acciones"><div class="record-actions"><a class="table-action" href="{{ route('medicos.edit', $medico) }}" aria-label="Editar {{ $medico->nombres }} {{ $medico->apellidos }}">Editar</a><a class="table-action" href="{{ route('medicos.estado.edit', $medico) }}" aria-label="{{ $medico->activo ? 'Desactivar' : 'Activar' }} {{ $medico->nombres }} {{ $medico->apellidos }}">{{ $medico->activo ? 'Desactivar' : 'Activar' }}</a></div></x-table-cell>
    </tr>
@empty
    <x-table-empty><div class="admin-empty"><h3>No encontramos médicos con estos filtros.</h3><p>Revisa la búsqueda o registra una nueva ficha.</p><a class="button primary" href="{{ route('medicos.create') }}">Registrar médico</a></div></x-table-empty>
@endforelse
</x-responsive-table>
<div class="admin-pagination">{{ $medicos->links() }}</div>
@endsection
