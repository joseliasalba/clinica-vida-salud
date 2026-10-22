@extends('layouts.especialidades')
@section('title', $especialidad->activo ? 'Desactivar especialidad' : 'Activar especialidad')
@section('specialty-title', $especialidad->activo ? 'Confirma la desactivación.' : 'Confirma la activación.')
@section('specialty-description', 'Revisa la especialidad y el cambio de estado antes de confirmar.')

@section('specialty-content')
@php
    $version = old('version', $especialidad->editVersion());
    $version = is_string($version) ? $version : '';
@endphp
@if($errors->has('version'))
    <div class="notice"><a href="{{ route('especialidades.estado.edit', $especialidad) }}">Recargar la especialidad actualizada</a> y revisar su estado antes de confirmar.</div>
@endif

<form class="admin-editor" method="POST" action="{{ route('especialidades.estado.update', $especialidad) }}">
    @csrf
    @method('PATCH')
    <input type="hidden" name="version" value="{{ $version }}">
    <input type="hidden" name="activo" value="{{ $especialidad->activo ? '0' : '1' }}">
    <section class="admin-panel">
        <h2 class="specialty-name">{{ $especialidad->nombre }}</h2>
        @if(filled($especialidad->descripcion))<p class="panel-description specialty-confirm-description">{{ $especialidad->descripcion }}</p>@endif
        <dl class="specialty-state-change">
            <div><dt>Estado actual</dt><dd><span class="state {{ $especialidad->activo ? 'active' : 'inactive' }}">{{ $especialidad->activo ? 'Activa' : 'Inactiva' }}</span></dd></div>
            <div><dt>Estado después de confirmar</dt><dd><span class="state {{ $especialidad->activo ? 'inactive' : 'active' }}">{{ $especialidad->activo ? 'Inactiva' : 'Activa' }}</span></dd></div>
        </dl>
        <p class="panel-description">{{ $especialidad->activo ? 'La especialidad dejará de estar activa en el catálogo. Sus datos y relaciones existentes se conservarán; podrás activarla nuevamente.' : 'La especialidad volverá a estar activa en el catálogo, conservando sus datos y relaciones existentes.' }}</p>
    </section>
    <div class="admin-form-footer">
        <a class="button secondary" href="{{ route('especialidades.index') }}">Cancelar</a>
        <button class="button primary" type="submit">{{ $especialidad->activo ? 'Confirmar desactivación' : 'Confirmar activación' }}</button>
    </div>
</form>
@endsection
