@extends('layouts.admin')
@section('title', 'Roles')
@section('admin-title', 'Cada equipo, sus funciones.')
@section('admin-description', 'Organiza los permisos en roles y asígnalos a las personas que los necesitan.')
@section('admin-actions')<a class="button primary" href="{{ route('roles.create') }}">＋ Crear rol</a>@endsection
@section('admin-content')
<div class="admin-result-heading"><h2>Roles de la clínica</h2><span>{{ $roles->total() }} roles</span></div>
<x-responsive-table label="Lista de roles" :columns="['rol' => 'Rol', 'permisos' => 'Permisos asignados y activos', 'estado' => 'Estado', 'acciones' => 'Acciones']">
@forelse($roles as $role)
    <tr role="row"><x-table-cell column="rol" heading><span class="user-label">{{ $role->nombre }}</span><small class="cell-note">{{ $role->descripcion }}</small></x-table-cell><x-table-cell column="permisos"><div class="tag-list">@forelse($permissions->get($role->id, collect()) as $permission)<span class="tag">{{ $permission->descripcion ?: $permission->codigo }}</span>@empty<span class="cell-note">Sin permisos activos</span>@endforelse</div></x-table-cell><x-table-cell column="estado"><span class="state {{ $role->activo ? 'active' : 'inactive' }}">{{ $role->activo ? 'Activo' : 'Inactivo' }}</span></x-table-cell><x-table-cell column="acciones"><a class="table-action" href="{{ route('roles.edit', $role->id) }}" aria-label="Editar rol {{ $role->nombre }}">Editar ↗</a></x-table-cell></tr>
@empty<x-table-empty><div class="admin-empty"><h3>Todavía no hay roles.</h3><p>Crea el primer rol con sus funciones.</p></div></x-table-empty>@endforelse
</x-responsive-table><div class="admin-pagination">{{ $roles->links() }}</div>
@endsection
