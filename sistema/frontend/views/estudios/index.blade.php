@extends('layouts.estudios')
@section('title', 'Estudios y resultados')
@section('study-title', $doctor ? 'Seguimiento de tus estudios solicitados.' : 'Tus estudios y resultados.')
@section('study-content')
<form class="admin-filters" method="GET" action="{{ route('estudios.index') }}">
    <div class="field"><label for="estado">Estado del estudio</label><select id="estado" name="estado"><option value="">Todos</option>@foreach(\App\Models\SolicitudEstudio::ESTADOS as $value => $label)<option value="{{ $value }}" @selected(request('estado') === $value)>{{ $label }}</option>@endforeach</select></div>
    <button class="button secondary" type="submit">Buscar</button><a class="filter-reset" href="{{ route('estudios.index') }}">Limpiar</a>
</form>
<div class="admin-result-heading"><h2>Solicitudes</h2><span>{{ $estudios->total() }} registros</span></div>
<x-responsive-table label="Estudios solicitados" :columns="['estudio' => 'Estudio', 'consulta' => 'Consulta', 'fecha' => 'Fecha de solicitud', 'estado' => 'Estado', 'resultado' => 'Resultado', 'acciones' => 'Acciones']">
@forelse($estudios as $estudio)
<tr role="row">
    <x-table-cell column="estudio" heading>{{ $estudio->nombre_estudio }}<small class="cell-note">Solicitud #{{ $estudio->id }}</small></x-table-cell>
    <x-table-cell column="consulta">{{ $consultas[$estudio->consulta_id]->nombres }} {{ $consultas[$estudio->consulta_id]->apellidos }}<small class="cell-note">{{ $consultas[$estudio->consulta_id]->cita_codigo }} · Consulta #{{ $estudio->consulta_id }}</small></x-table-cell>
    <x-table-cell column="fecha">{{ $estudio->fecha_solicitud->format('d/m/Y H:i') }}</x-table-cell>
    <x-table-cell column="estado">{{ \App\Models\SolicitudEstudio::ESTADOS[$estudio->estado] }}</x-table-cell>
    <x-table-cell column="resultado">{{ $estudio->resultado?->estado_publicacion === 'publicado' ? 'Disponible' : ($doctor ? ucfirst($estudio->resultado?->estado_publicacion ?? 'Sin resultado') : 'Aún no disponible') }}</x-table-cell>
    <x-table-cell column="acciones"><a class="table-action" href="{{ route('estudios.show', $estudio) }}">Ver estudio</a></x-table-cell>
</tr>
@empty
<x-table-empty><div class="admin-empty"><h3>No hay estudios para mostrar.</h3><p>{{ $doctor ? 'Solicita un estudio desde una consulta médica registrada.' : 'Aquí aparecerán los estudios que solicite tu médico.' }}</p></div></x-table-empty>
@endforelse
</x-responsive-table>
<div class="admin-pagination">{{ $estudios->links() }}</div>
@endsection
