@extends('layouts.auth')
@section('title', 'Espera un momento')
@section('form')
    <h2 id="form-title">Espera un momento.</h2>
    <p class="form-description">Recibimos demasiadas solicitudes. Por favor, espera unos minutos antes de intentarlo de nuevo.</p>
    <a class="button primary" href="{{ route('login') }}">Volver al acceso</a>
@endsection
