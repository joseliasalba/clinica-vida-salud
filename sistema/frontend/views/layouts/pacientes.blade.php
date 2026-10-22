@extends('layouts.app')
@section('content')
<div class="admin-shell">
    <div class="admin-breadcrumb"><a href="{{ route('inicio') }}">← Mi inicio</a><span>Pacientes</span></div>
    <header class="admin-heading"><div><span class="eyebrow">RECEPCIÓN · VIDA SALUD</span><h1>@yield('patient-title')</h1><p>@yield('patient-description')</p></div><div class="admin-actions">@yield('patient-actions')</div></header>
    <nav class="admin-nav" aria-label="Gestión de pacientes"><a href="{{ route('pacientes.index') }}" @if(request()->routeIs('pacientes.index')) aria-current="page" @endif>Pacientes</a><a href="{{ route('pacientes.create') }}" @if(request()->routeIs('pacientes.create')) aria-current="page" @endif>Registrar paciente</a></nav>
    <x-messages />
    @yield('patient-content')
</div>
@endsection
