@extends('layouts.admin')
@section('title', 'Permisos')
@section('admin-title', 'El acceso empieza por una función.')
@section('admin-description', 'Consulta el catálogo de funciones y controla cuáles están habilitadas en la clínica.')
@section('admin-content')
<div class="notice">Desactivar un permiso retira esa función de todos los roles que lo tienen. Sus asignaciones se conservan para una futura reactivación.</div>
<x-responsive-table label="Catálogo de permisos" :columns="['funcion' => 'Función', 'estado' => 'Estado', 'acciones' => 'Cambiar estado']">
@foreach($permissions as $permission)
    <tr role="row"><x-table-cell column="funcion" heading>{{ $permission->descripcion ?: $permission->codigo }}</x-table-cell><x-table-cell column="estado"><span class="state {{ $permission->activo ? 'active' : 'inactive' }}">{{ $permission->activo ? 'Activo' : 'Inactivo' }}</span></x-table-cell><x-table-cell column="acciones"><form method="POST" action="{{ route('permisos.update', $permission->id) }}">@csrf @method('PATCH')<input type="hidden" name="activo" value="{{ $permission->activo ? '0' : '1' }}"><button class="button secondary compact" type="submit" aria-label="{{ $permission->activo ? 'Desactivar' : 'Activar' }} permiso {{ $permission->descripcion }}">{{ $permission->activo ? 'Desactivar' : 'Activar' }}</button></form></x-table-cell></tr>
@endforeach
</x-responsive-table>
@endsection
