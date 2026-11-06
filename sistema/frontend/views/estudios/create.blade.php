@extends('layouts.estudios')
@section('title', 'Solicitar estudio')
@section('study-title', 'Solicita un estudio para tu paciente.')
@section('study-description', 'Selecciona una de tus consultas registradas. La identidad del paciente se obtiene de esa consulta.')
@section('study-content')
@php
    $text = static fn (string $field): string => is_string(old($field)) ? old($field) : '';
@endphp
<form class="admin-filters" method="GET" action="{{ route('estudios.create') }}">
    <div class="field"><label for="q">Buscar consulta por paciente, código o cita</label><input id="q" name="q" maxlength="100" value="{{ is_string(request('q')) ? request('q') : '' }}"></div><button class="button secondary" type="submit">Buscar</button><a class="filter-reset" href="{{ route('estudios.create') }}">Limpiar</a>
</form>
@if($consultas->isEmpty())
<div class="admin-empty"><h2>No hay consultas disponibles.</h2><p>Se necesita una consulta registrada para una cita en atención o atendida, vinculada a tu médico. Si buscaste un paciente, prueba con otro filtro.</p></div>
@else
<form class="admin-editor" method="POST" action="{{ route('estudios.store') }}">
    @csrf
    <section class="admin-panel"><h2>Consulta de origen</h2><div class="field"><label for="consulta_id">Paciente y consulta</label><select id="consulta_id" name="consulta_id" required><option value="">Selecciona una consulta</option>@foreach($consultas as $consulta)<option value="{{ $consulta->id }}" @selected(is_scalar(old('consulta_id', $consultaSeleccionada)) && (string)old('consulta_id', $consultaSeleccionada) === (string)$consulta->id)>{{ $consulta->nombres }} {{ $consulta->apellidos }} · {{ $consulta->codigo }} · {{ $consulta->cita_codigo }} · {{ \Illuminate\Support\Carbon::parse($consulta->fecha_atencion)->format('d/m/Y') }}</option>@endforeach</select><small>Se muestran hasta 15 consultas por página. Puedes buscar al paciente antes de completar el formulario.</small></div></section>
    <section class="admin-panel"><h2>Estudio solicitado</h2><div class="field"><label for="nombre_estudio">Nombre del estudio</label><input id="nombre_estudio" name="nombre_estudio" required maxlength="255" value="{{ $text('nombre_estudio') }}"></div><div class="field"><label for="indicaciones">Indicaciones para el estudio (opcional)</label><textarea id="indicaciones" name="indicaciones" maxlength="5000" rows="4">{{ $text('indicaciones') }}</textarea></div></section>
    <div class="admin-form-footer"><a class="button secondary" href="{{ route('estudios.index') }}">Volver</a><button class="button primary" type="submit">Registrar solicitud</button></div>
</form>
@endif
<div class="admin-pagination">{{ $consultas->links() }}</div>
@endsection
