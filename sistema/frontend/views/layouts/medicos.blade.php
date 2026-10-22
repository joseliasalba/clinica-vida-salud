@extends('layouts.app')
@section('content')
<div class="admin-shell">
    <div class="admin-breadcrumb"><a href="{{ route('inicio') }}">← Mi inicio</a><span>Médicos y especialidades</span></div>
    <header class="admin-heading"><div><span class="eyebrow">ADMINISTRACIÓN · VIDA SALUD</span><h1>@yield('doctor-title')</h1><p>@yield('doctor-description')</p></div></header>
    <nav class="admin-nav" aria-label="Gestión de médicos y especialidades"><a href="{{ route('medicos.index') }}" @if(request()->routeIs('medicos.index')) aria-current="page" @endif>Médicos</a><a href="{{ route('medicos.create') }}" @if(request()->routeIs('medicos.create')) aria-current="page" @endif>Registrar médico</a><a href="{{ route('especialidades.index') }}">Especialidades</a></nav>
    <x-messages />
    @yield('doctor-content')
</div>
@endsection
