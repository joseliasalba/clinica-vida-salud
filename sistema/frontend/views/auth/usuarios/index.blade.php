@extends('layouts.admin')
@section('title', 'Usuarios')
@section('admin-title', 'Las personas detrás de cada cuenta.')
@section('admin-description', 'Busca usuarios, organiza sus roles y administra su acceso a la clínica.')
@section('admin-actions')<a class="button primary" href="{{ route('usuarios.create') }}">＋ Crear usuario</a>@endsection
@section('admin-content')
<form class="admin-filters" method="GET" action="{{ route('usuarios.index') }}">
    <div class="field"><label for="q">Buscar por usuario o correo</label><input type="search" id="q" name="q" maxlength="100" value="{{ request('q') }}" placeholder="Escribe un nombre de usuario o correo"></div>
    <div class="field"><label for="estado">Estado</label><select id="estado" name="estado"><option value="">Todos</option><option value="activo" @selected(request('estado') === 'activo')>Activos</option><option value="inactivo" @selected(request('estado') === 'inactivo')>Inactivos</option></select></div>
    <button class="button secondary" type="submit">Buscar</button><a class="filter-reset" href="{{ route('usuarios.index') }}">Limpiar</a>
</form>
<div class="admin-result-heading"><h2>Cuentas de usuario</h2><span>{{ $users->total() }} {{ $users->total() === 1 ? 'cuenta' : 'cuentas' }}</span></div>
<x-responsive-table label="Lista de usuarios" :columns="['usuario' => 'Usuario', 'correo' => 'Correo', 'roles' => 'Roles asignados', 'estado' => 'Estado', 'acciones' => 'Acciones']">
@forelse($users as $usuario)
    <tr role="row"><x-table-cell column="usuario" heading><span class="user-label">{{ $usuario->nombre_usuario }}</span>@if($usuario->id === auth()->id())<small class="cell-note">Tu cuenta</small>@endif</x-table-cell><x-table-cell column="correo">{{ $usuario->correo }}</x-table-cell><x-table-cell column="roles"><div class="tag-list">
        @forelse($assignments->get($usuario->id, collect()) as $assignment)
            @php($valid = $assignment->rol_activo && $assignment->fecha_asignacion <= now()->toDateTimeString() && (!$assignment->fecha_expiracion || $assignment->fecha_expiracion > now()->toDateTimeString()))
            <span class="tag {{ $valid ? '' : 'muted' }}">{{ $assignment->nombre }} @if(!$valid) · sin vigencia @endif</span>
        @empty<span class="cell-note">Sin roles vigentes</span>@endforelse
    </div></x-table-cell><x-table-cell column="estado"><span class="state {{ $usuario->activo ? 'active' : 'inactive' }}">{{ $usuario->activo ? 'Activo' : 'Inactivo' }}</span></x-table-cell><x-table-cell column="acciones"><a class="table-action" href="{{ route('usuarios.edit', $usuario) }}" aria-label="Editar usuario {{ $usuario->nombre_usuario }}">Editar ↗</a></x-table-cell></tr>
@empty
    <x-table-empty><div class="admin-empty"><h3>No encontramos usuarios con estos filtros.</h3><p>Prueba con otro texto o limpia la búsqueda.</p></div></x-table-empty>
@endforelse
</x-responsive-table>
<div class="admin-pagination">{{ $users->links() }}</div>
@endsection
