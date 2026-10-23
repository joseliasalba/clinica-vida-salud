@extends('layouts.app')
@section('content')
<div class="admin-shell">
    <div class="admin-breadcrumb"><a href="{{ route('inicio') }}">Mi inicio</a><span>Citas médicas</span></div>
    <header class="admin-heading"><div><span class="eyebrow">ATENCIÓN · VIDA SALUD</span><h1>@yield('citas-title', 'Gestionar citas médicas')</h1><p>@yield('citas-description', 'Consulta las citas y registra sus cambios de forma segura.')</p></div></header>
    <nav class="admin-nav" aria-label="Gestión de citas"><a href="{{ route('citas.index') }}" @if(request()->routeIs('citas.index')) aria-current="page" @endif>Citas</a></nav>
    <x-messages />
    @yield('citas-content')
</div>
@endsection
