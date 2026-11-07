@extends('layouts.app')
@section('title', 'Turnos de atención')
@section('content')
<div class="admin-shell">
    <div class="admin-breadcrumb"><a href="{{ route('inicio') }}">Mi inicio</a><span>Turnos de hoy</span></div>
    <header class="admin-heading"><div><span class="eyebrow">ATENCIÓN · VIDA SALUD</span><h1>{{ $patient ? 'Mi turno de hoy' : 'Turnos de hoy' }}</h1><p>La lista se ordena por la llegada registrada. El orden es orientativo y puede cambiar según las necesidades de atención.</p></div></header>
    <x-messages />
    <p><a href="{{ route('turnos.index') }}">Actualizar información</a> @if($manager) · <a href="{{ route('citas.gestion') }}">Registrar otra llegada</a>@endif</p>
    @php($columns=$patient ? ['codigo'=>'Cita','medico'=>'Médico y especialidad','estado'=>'Estado','posicion'=>'Personas antes'] : ['codigo'=>'Cita','paciente'=>'Paciente','medico'=>'Médico y especialidad','hora'=>'Hora programada','llegada'=>'Llegada','estado'=>'Estado'])
    <x-responsive-table :columns="$columns" label="Turnos de atención de hoy">
    @forelse($turnos as $turno)
        <tr role="row">
            <x-table-cell column="codigo" heading>{{ $turno->codigo }}</x-table-cell>
            @if(!$patient)<x-table-cell column="paciente">{{ $turno->nombres }} {{ $turno->apellidos }}</x-table-cell>@endif
            <x-table-cell column="medico">{{ $turno->medico_nombre }} {{ $turno->medico_apellido }}<br>{{ $turno->especialidad }}</x-table-cell>
            @if(!$patient)<x-table-cell column="hora">{{ \Carbon\CarbonImmutable::parse($turno->inicio)->format('H:i') }}</x-table-cell><x-table-cell column="llegada">{{ \Carbon\CarbonImmutable::parse($turno->llegada_en)->timezone('America/La_Paz')->format('H:i') }}</x-table-cell>@endif
            <x-table-cell column="estado">{{ $turno->estado === 'en_curso' ? 'En atención' : 'Llegada registrada' }}</x-table-cell>
            @if($patient)<x-table-cell column="posicion">{{ $turno->estado === 'en_curso' ? 'Ya estás en atención' : $turno->antes }}</x-table-cell>@endif
        </tr>
    @empty<x-table-empty>{{ $patient ? 'No tienes una llegada registrada para hoy. Acércate a recepción al llegar.' : 'No hay llegadas registradas para mostrar.' }}</x-table-empty>@endforelse
    </x-responsive-table>
</div>
@endsection
