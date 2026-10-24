@extends('layouts.app')
@section('content')
<div class="admin-shell">
    <div class="admin-breadcrumb"><a href="{{ route('inicio') }}">← Mi inicio</a><span>Horarios de atención</span></div>
    <header class="admin-heading"><div><span class="eyebrow">AGENDA · VIDA SALUD</span><h1>@yield('schedule-title')</h1><p>@yield('schedule-description') Todas las horas corresponden a Bolivia (America/La_Paz).</p></div></header>
    <nav class="admin-nav" aria-label="Configuración de horarios"><a href="{{ route('horarios.index') }}" @if(request()->routeIs('horarios.index')) aria-current="page" @endif>Horarios</a><a href="{{ route('horarios.create') }}" @if(request()->routeIs('horarios.create')) aria-current="page" @endif>Registrar horario</a><a href="{{ route('horarios.bloqueos.index') }}" @if(request()->routeIs('horarios.bloqueos.index')) aria-current="page" @endif>Bloqueos y ausencias</a><a href="{{ route('horarios.bloqueos.create') }}" @if(request()->routeIs('horarios.bloqueos.create')) aria-current="page" @endif>Registrar bloqueo</a></nav>
    <x-messages />
    @yield('schedule-content')
</div>
@endsection
