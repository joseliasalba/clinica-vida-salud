@extends('layouts.especialidades')
@section('title', 'Especialidades médicas')
@section('specialty-title', 'Especialidades para cada necesidad.')
@section('specialty-description', 'Consulta el catálogo de especialidades médicas, actualiza sus datos y administra cuáles están activas.')

@section('specialty-content')
<form class="admin-filters" method="GET" action="{{ route('especialidades.index') }}">
    <div class="field">
        <label for="q">Buscar por nombre o descripción</label>
        <input type="search" id="q" name="q" maxlength="100" value="{{ is_string(request('q')) ? request('q') : '' }}" placeholder="Por ejemplo, Cardiología" @error('q') aria-invalid="true" aria-describedby="form-errors" @enderror>
    </div>
    <div class="field">
        <label for="estado">Estado de la especialidad</label>
        <select id="estado" name="estado" @error('estado') aria-invalid="true" aria-describedby="form-errors" @enderror>
            <option value="">Todos</option>
            <option value="activo" @selected(request('estado') === 'activo')>Activas</option>
            <option value="inactivo" @selected(request('estado') === 'inactivo')>Inactivas</option>
        </select>
    </div>
    <button class="button secondary" type="submit">Buscar</button>
    <a class="filter-reset" href="{{ route('especialidades.index') }}">Limpiar</a>
</form>

<div class="admin-result-heading">
    <h2>Catálogo de especialidades</h2>
    <span>{{ $especialidades->total() }} {{ $especialidades->total() === 1 ? 'especialidad' : 'especialidades' }}</span>
</div>
<x-responsive-table label="Lista de especialidades médicas" :columns="['especialidad' => 'Especialidad', 'descripcion' => 'Descripción', 'estado' => 'Estado', 'acciones' => 'Acciones']">
        @forelse($especialidades as $especialidad)
            <tr role="row">
                <x-table-cell column="especialidad" heading><span class="user-label">{{ $especialidad->nombre }}</span></x-table-cell>
                <x-table-cell column="descripcion"><span class="specialty-description">{{ $especialidad->descripcion ?? 'Sin descripción' }}</span></x-table-cell>
                <x-table-cell column="estado"><span class="state {{ $especialidad->activo ? 'active' : 'inactive' }}">{{ $especialidad->activo ? 'Activa' : 'Inactiva' }}</span></x-table-cell>
                <x-table-cell column="acciones">
                    <div class="record-actions">
                        <a class="table-action" href="{{ route('especialidades.edit', $especialidad) }}" aria-label="Editar {{ $especialidad->nombre }}">Editar</a>
                        <a class="table-action" href="{{ route('especialidades.estado.edit', $especialidad) }}" aria-label="{{ $especialidad->activo ? 'Desactivar' : 'Activar' }} {{ $especialidad->nombre }}">{{ $especialidad->activo ? 'Desactivar' : 'Activar' }}</a>
                    </div>
                </x-table-cell>
            </tr>
        @empty
            <x-table-empty><div class="admin-empty">
                <h3>No encontramos especialidades con estos filtros.</h3>
                <p>Prueba con otro nombre o limpia los filtros. Puedes registrar una especialidad si aún no existe.</p>
                <a class="button primary" href="{{ route('especialidades.create') }}">Registrar especialidad</a>
            </div></x-table-empty>
        @endforelse
</x-responsive-table>
<div class="admin-pagination">{{ $especialidades->links() }}</div>
@endsection
