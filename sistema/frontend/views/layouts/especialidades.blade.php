@extends('layouts.app')

@section('content')
<div class="admin-shell">
    <div class="admin-breadcrumb"><a href="{{ route('inicio') }}">← Mi inicio</a><span>Especialidades médicas</span></div>
    <header class="admin-heading">
        <div>
            <span class="eyebrow">ADMINISTRACIÓN · VIDA SALUD</span>
            <h1>@yield('specialty-title')</h1>
            <p>@yield('specialty-description')</p>
        </div>
    </header>
    <nav class="admin-nav" aria-label="Gestión de especialidades médicas">
        <a href="{{ route('medicos.index') }}">Médicos</a>
        <a href="{{ route('especialidades.index') }}" @if(request()->routeIs('especialidades.index')) aria-current="page" @endif>Especialidades</a>
        <a href="{{ route('especialidades.create') }}" @if(request()->routeIs('especialidades.create')) aria-current="page" @endif>Registrar especialidad</a>
    </nav>
    <x-messages />
    @yield('specialty-content')
</div>
@endsection
