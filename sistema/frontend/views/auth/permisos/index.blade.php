@extends('layouts.admin')
@section('title', 'Permisos')
@section('admin-title', 'El acceso empieza por una función.')
@section('admin-description', 'Consulta el catálogo de funciones y controla cuáles están habilitadas en la clínica.')
@section('admin-content')
<div class="notice">Desactivar un permiso retira esa función de todos los roles que lo tienen. Sus asignaciones se conservan para una futura reactivación.</div>
<div class="admin-table-wrap" role="region" aria-label="Catálogo de permisos" tabindex="0"><table class="admin-table"><thead><tr><th scope="col">Función</th><th scope="col">Estado</th><th scope="col">Cambiar estado</th></tr></thead><tbody>
@foreach($permissions as $permission)
    <tr><th scope="row">{{ $permission->descripcion ?: $permission->codigo }}</th><td><span class="state {{ $permission->activo ? 'active' : 'inactive' }}">{{ $permission->activo ? 'Activo' : 'Inactivo' }}</span></td><td><form method="POST" action="{{ route('permisos.update', $permission->id) }}">@csrf @method('PATCH')<input type="hidden" name="activo" value="{{ $permission->activo ? '0' : '1' }}"><button class="button secondary compact" type="submit" aria-label="{{ $permission->activo ? 'Desactivar' : 'Activar' }} permiso {{ $permission->descripcion }}">{{ $permission->activo ? 'Desactivar' : 'Activar' }}</button></form></td></tr>
@endforeach
</tbody></table></div>
@endsection
