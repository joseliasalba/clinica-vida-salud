@extends('layouts.admin')
@section('title', 'Roles')
@section('admin-title', 'Cada equipo, sus funciones.')
@section('admin-description', 'Organiza los permisos en roles y asígnalos a las personas que los necesitan.')
@section('admin-actions')<a class="button primary" href="{{ route('roles.create') }}">＋ Crear rol</a>@endsection
@section('admin-content')
<div class="admin-result-heading"><h2>Roles de la clínica</h2><span>{{ $roles->total() }} roles</span></div>
<div class="admin-table-wrap" role="region" aria-label="Lista de roles" tabindex="0"><table class="admin-table"><thead><tr><th scope="col">Rol</th><th scope="col">Permisos asignados y activos</th><th scope="col">Estado</th><th scope="col">Acciones</th></tr></thead><tbody>
@forelse($roles as $role)
    <tr><th scope="row"><span class="user-label">{{ $role->nombre }}</span><small class="cell-note">{{ $role->descripcion }}</small></th><td><div class="tag-list">@forelse($permissions->get($role->id, collect()) as $permission)<span class="tag">{{ $permission->descripcion ?: $permission->codigo }}</span>@empty<span class="cell-note">Sin permisos activos</span>@endforelse</div></td><td><span class="state {{ $role->activo ? 'active' : 'inactive' }}">{{ $role->activo ? 'Activo' : 'Inactivo' }}</span></td><td><a class="table-action" href="{{ route('roles.edit', $role->id) }}" aria-label="Editar rol {{ $role->nombre }}">Editar ↗</a></td></tr>
@empty<tr><td colspan="4"><div class="admin-empty"><h3>Todavía no hay roles.</h3><p>Crea el primer rol con sus funciones.</p></div></td></tr>@endforelse
</tbody></table></div><div class="admin-pagination">{{ $roles->links() }}</div>
@endsection
