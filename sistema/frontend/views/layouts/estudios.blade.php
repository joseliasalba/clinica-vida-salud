@extends('layouts.app')
@section('content')
<div class="admin-shell">
    <div class="admin-breadcrumb"><a href="{{ route('inicio') }}">← Mi inicio</a><span>Estudios y resultados</span></div>
    <header class="admin-heading"><div><span class="eyebrow">ATENCIÓN · VIDA SALUD</span><h1>@yield('study-title', 'Estudios y resultados')</h1><p>@yield('study-description', 'Consulta el seguimiento de los estudios y sus resultados disponibles.')</p></div></header>
    <nav class="admin-nav" aria-label="Estudios"><a href="{{ route('estudios.index') }}" @if(request()->routeIs('estudios.index')) aria-current="page" @endif>Mis estudios</a>@can('estudios.solicitar')<a href="{{ route('estudios.create') }}" @if(request()->routeIs('estudios.create')) aria-current="page" @endif>Solicitar estudio</a>@endcan</nav>
    <x-messages />
    @yield('study-content')
</div>
@endsection
