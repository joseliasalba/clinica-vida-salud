@extends('layouts.app')
@section('content')
<div class="admin-shell">
    <div class="admin-breadcrumb"><a href="{{ route('inicio') }}">← Mi inicio</a><span>Administración de acceso</span></div>
    <header class="admin-heading"><div><span class="eyebrow">CUIDAMOS CADA ACCESO</span><h1>@yield('admin-title')</h1><p>@yield('admin-description')</p></div><div class="admin-actions">@yield('admin-actions')</div></header>
    <x-admin-nav />
    <x-messages />
    @yield('admin-content')
</div>
@endsection
