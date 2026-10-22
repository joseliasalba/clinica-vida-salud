@extends('layouts.auth')
@section('title', 'Formulario vencido')
@section('form')
    <h2 id="form-title">Volvamos a empezar.</h2>
    <p class="form-description">El formulario venció. Abre una página nueva para continuar con seguridad.</p>
    <a class="button primary" href="{{ route('login') }}">Volver al acceso</a>
@endsection
