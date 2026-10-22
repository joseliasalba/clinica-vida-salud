@extends('layouts.admin')
@section('title', $role ? 'Editar rol' : 'Crear rol')
@section('admin-title', $role ? 'Ajusta las funciones del equipo.' : 'Un nuevo rol para tu equipo.')
@section('admin-description', 'Selecciona con cuidado las funciones que recibirán las cuentas vinculadas a este rol.')
@section('admin-content')
@php
    $selected = session()->hasOldInput() ? old('permisos', []) : $selectedPermissions;
    $selected = is_array($selected) ? $selected : [];
    $baseRole = $role && array_key_exists($role->nombre, config('permisos.roles'));
    $text = static fn (string $field, ?string $fallback = null): string => is_string(old($field, $fallback)) ? old($field, $fallback) : '';
@endphp
<form class="admin-editor" method="POST" action="{{ $role ? route('roles.update', $role->id) : route('roles.store') }}">@csrf @if($role) @method('PUT') @endif
    <section class="admin-panel"><h2>Identificación del rol</h2><div class="admin-form-grid">
        <div class="field"><label for="nombre">Nombre del rol</label><input id="nombre" name="nombre" required maxlength="50" value="{{ $text('nombre', $role?->nombre) }}" @readonly($baseRole) @error('nombre') aria-invalid="true" aria-describedby="form-errors" @enderror><small>{{ $baseRole ? 'El nombre de este rol base se conserva.' : 'Usa letras sin acentos, números o guion bajo; empieza con una letra.' }}</small></div>
        <div class="field"><label for="descripcion">Descripción (opcional)</label><input id="descripcion" name="descripcion" maxlength="255" value="{{ $text('descripcion', $role?->descripcion) }}"></div>
    </div></section>
    <section class="admin-panel"><h2>Permisos del rol</h2><p class="panel-description">Los cambios afectan a todas las cuentas que tengan este rol. Selecciona al menos un permiso activo.</p><fieldset class="choice-grid"><legend class="sr-only">Selecciona los permisos del rol</legend>
        @foreach($permissions as $permission)
            <label class="choice {{ !$permission->activo ? 'disabled' : '' }}"><input type="checkbox" name="permisos[]" value="{{ $permission->id }}" @checked($permission->activo && in_array($permission->id, $selected)) @disabled(!$permission->activo)><span><strong>{{ $permission->descripcion ?: $permission->codigo }}</strong><small>{{ $permission->activo ? ($permission->codigo === 'usuarios.gestionar' ? 'Permite administrar todas las cuentas, roles y permisos.' : 'Función habilitada para asignar.') : 'Permiso desactivado; se conserva su asignación previa.' }}</small></span></label>
        @endforeach
    </fieldset></section>
    <section class="admin-panel"><h2>Estado del rol</h2><input type="hidden" name="activo" value="0"><label class="choice inline"><input type="checkbox" name="activo" value="1" @checked(old('activo', $role?->activo ?? true))><span><strong>Rol activo</strong><small>Al desactivarlo, sus permisos dejan de aplicarse a todas las cuentas vinculadas.</small></span></label></section>
    <div class="admin-form-footer"><a class="button secondary" href="{{ route('roles.index') }}">Cancelar</a><button class="button primary" type="submit">{{ $role ? 'Guardar cambios' : 'Crear rol' }}</button></div>
</form>
@endsection
