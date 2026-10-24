@extends('layouts.app')
@section('content')
<div class="admin-shell booking-shell">
    <div class="admin-breadcrumb"><a href="{{ route('inicio') }}">← Mi inicio</a><span>Reserva de citas</span></div>
    <header class="admin-heading"><div><span class="eyebrow">TU PRÓXIMA VISITA</span><h1>@yield('booking-title')</h1><p>@yield('booking-description') Las horas corresponden a Bolivia (America/La_Paz).</p></div></header>
    <x-messages />
    @yield('booking-content')
</div>
@endsection