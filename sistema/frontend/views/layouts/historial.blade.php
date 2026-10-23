@extends('layouts.app')
@section('content')
<div class="admin-shell">
    <div class="admin-breadcrumb"><a href="{{ route('inicio') }}">Mi inicio</a><span>Historial de citas</span></div>
    <header class="admin-heading"><div><span class="eyebrow">ATENCIÓN · VIDA SALUD</span><h1>Historial de citas</h1><p>Consulta las fechas, profesionales y estados de las citas registradas.</p></div></header>
    <x-messages />
    @yield('historial-content')
</div>
@endsection
