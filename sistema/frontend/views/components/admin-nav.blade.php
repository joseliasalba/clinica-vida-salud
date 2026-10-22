<nav class="admin-nav" aria-label="Administración de acceso">
    <a href="{{ route('usuarios.index') }}" @if(request()->routeIs('usuarios.*')) aria-current="page" @endif>Usuarios</a>
    <a href="{{ route('roles.index') }}" @if(request()->routeIs('roles.*')) aria-current="page" @endif>Roles</a>
    <a href="{{ route('permisos.index') }}" @if(request()->routeIs('permisos.*')) aria-current="page" @endif>Permisos</a>
</nav>
