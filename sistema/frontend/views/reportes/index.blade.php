@extends('layouts.app')
@section('title', 'Reportes administrativos')
@section('content')
<div class="admin-page">
    <h1>Reportes administrativos</h1>
    <x-messages />
    <form method="GET" action="{{ route('reportes.index') }}" class="admin-filters">
        <div class="field"><label for="desde">Desde</label><input id="desde" name="desde" type="date" value="{{ old('desde', $filters['desde'] ?? '') }}"></div>
        <div class="field"><label for="hasta">Hasta</label><input id="hasta" name="hasta" type="date" value="{{ old('hasta', $filters['hasta'] ?? '') }}"></div>
        <div class="field"><label for="medico_id">Médico</label><select id="medico_id" name="medico_id"><option value="">Todos</option>@foreach($medicos as $medico)<option value="{{ $medico->id }}" @selected(($filters['medico_id'] ?? null) == $medico->id)>{{ $medico->apellidos }}, {{ $medico->nombres }}</option>@endforeach</select></div>
        <div class="field"><label for="especialidad_id">Especialidad</label><select id="especialidad_id" name="especialidad_id"><option value="">Todas</option>@foreach($especialidades as $especialidad)<option value="{{ $especialidad->id }}" @selected(($filters['especialidad_id'] ?? null) == $especialidad->id)>{{ $especialidad->nombre }}</option>@endforeach</select></div>
        <div class="field"><label for="estado">Estado</label><select id="estado" name="estado"><option value="">Todos</option>@foreach($estados as $estado => $label)<option value="{{ $estado }}" @selected(($filters['estado'] ?? '') === $estado)>{{ $label }}</option>@endforeach</select></div>
        <button type="submit" class="button primary">Generar reporte</button>
        <a class="filter-reset" href="{{ route('reportes.index') }}">Limpiar filtros</a>
    </form>
    <p><strong>{{ $citas->total() }}</strong> citas · <strong>{{ $totalPacientes }}</strong> pacientes distintos en el resultado</p>
    <x-responsive-table label="Reporte de citas" :columns="['cita' => 'Cita', 'paciente' => 'Paciente', 'fecha' => 'Fecha y hora', 'medico' => 'Médico', 'especialidad' => 'Especialidad', 'estado' => 'Estado']">
        @forelse($citas as $cita)
            <tr role="row">
                <x-table-cell column="cita" heading>{{ $cita->codigo }}</x-table-cell>
                <x-table-cell column="paciente">{{ $cita->paciente->codigo }}</x-table-cell>
                <x-table-cell column="fecha">{{ $cita->inicio->format('d/m/Y H:i') }}</x-table-cell>
                <x-table-cell column="medico">{{ $cita->medico->nombres }} {{ $cita->medico->apellidos }}</x-table-cell>
                <x-table-cell column="especialidad">{{ $cita->especialidad->nombre }}</x-table-cell>
                <x-table-cell column="estado">{{ $cita->estadoLabel() }}</x-table-cell>
            </tr>
        @empty
            <x-table-empty>No hay citas para los filtros seleccionados.</x-table-empty>
        @endforelse
    </x-responsive-table>
    <div class="admin-pagination">{{ $citas->links() }}</div>
</div>
@endsection
