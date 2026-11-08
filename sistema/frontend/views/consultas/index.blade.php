@extends('layouts.app')
@section('title', 'Consultas médicas')
@section('content')
<div class="admin-page">
    <h1>Consultas médicas</h1>
    <p>Selecciona una de tus citas en atención o atendidas para registrar o revisar la consulta.</p>
    <x-responsive-table label="Citas para consulta" :columns="['cita' => 'Cita', 'paciente' => 'Paciente', 'fecha' => 'Fecha y hora', 'estado' => 'Estado', 'accion' => 'Acción']">
        @forelse($citas as $cita)
            <tr role="row">
                <x-table-cell column="cita" heading>{{ $cita->codigo }}</x-table-cell>
                <x-table-cell column="paciente">{{ $cita->paciente->nombres }} {{ $cita->paciente->apellidos }}</x-table-cell>
                <x-table-cell column="fecha">{{ $cita->inicio->format('d/m/Y H:i') }}</x-table-cell>
                <x-table-cell column="estado">{{ $cita->estadoLabel() }}</x-table-cell>
                <x-table-cell column="accion"><a class="button secondary" href="{{ route('consultas.show', $cita->id) }}">Abrir consulta</a></x-table-cell>
            </tr>
        @empty
            <x-table-empty>No tienes citas en atención o atendidas.</x-table-empty>
        @endforelse
    </x-responsive-table>
    <div class="admin-pagination">{{ $citas->links() }}</div>
</div>
@endsection
