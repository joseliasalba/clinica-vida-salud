@extends('layouts.app')
@section('title', 'Acceso no permitido')
@section('content')<section class="admin-shell"><div class="admin-panel"><span class="eyebrow">ACCESO RESTRINGIDO</span><h1>No tienes permiso para esta función.</h1><p>Si necesitas este acceso para tu trabajo, contacta a la administración de la clínica.</p><a class="button primary" href="{{ route('inicio') }}">Volver a mi inicio</a></div></section>@endsection
