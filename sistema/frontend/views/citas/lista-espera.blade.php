@extends('layouts.app')
@section('title', 'Lista de espera')
@section('content')
<div class="admin-shell">
    <div class="admin-breadcrumb"><a href="{{ route('inicio') }}">Mi inicio</a><span>Lista de espera</span></div>
    <header class="admin-heading"><div><span class="eyebrow">CITAS · VIDA SALUD</span><h1>Lista de espera</h1><p>La inscripción no reserva un turno. Recepción revisa las solicitudes y contacta al paciente cuando encuentra una opción.</p></div></header>
    <x-messages />
    <p><a href="{{ route('citas.index') }}">Consultar turnos e inscribirse</a>@if($manager) · <a href="{{ route('citas.gestion') }}">Gestionar citas</a>@endif</p>
    <form class="admin-filters" method="GET" action="{{ route('lista-espera.index') }}">
        <div class="field"><label for="fecha">Fecha solicitada</label><input id="fecha" type="date" name="fecha" value="{{ request('fecha') }}"></div>
        <div class="field"><label for="estado">Estado</label><select id="estado" name="estado"><option value="">Activas próximas</option>@foreach(['esperando'=>'Esperando','contactado'=>'Contactado','finalizado'=>'Finalizado','cancelado'=>'Cancelado'] as $value=>$label)<option value="{{ $value }}" @selected(request('estado') === $value)>{{ $label }}</option>@endforeach</select></div>
        <button class="button secondary">Buscar</button><a href="{{ route('lista-espera.index') }}">Limpiar</a>
    </form>
    @php($columns=['paciente'=>'Paciente','solicitud'=>'Solicitud','registro'=>'Inscripción','estado'=>'Estado','acciones'=>'Acciones'])
    <x-responsive-table :columns="$columns" label="Solicitudes de lista de espera">
    @forelse($solicitudes as $item)
        <tr role="row">
            <x-table-cell column="paciente" heading>{{ $item->nombres }} {{ $item->apellidos }} · {{ $item->codigo }}@if($manager)<br>Teléfono: {{ $item->telefono }}@endif</x-table-cell>
            <x-table-cell column="solicitud">{{ $item->especialidad }}<br>{{ \Carbon\CarbonImmutable::parse($item->fecha)->format('d/m/Y') }}<br>{{ $item->medico_nombre ? $item->medico_nombre.' '.$item->medico_apellido : 'Cualquier médico' }}</x-table-cell>
            <x-table-cell column="registro">{{ \Carbon\CarbonImmutable::parse($item->created_at)->format('d/m/Y H:i') }}</x-table-cell>
            <x-table-cell column="estado">{{ ucfirst($item->estado) }}</x-table-cell>
            <x-table-cell column="acciones"><div class="citas-actions">
                @if(in_array($item->estado, ['esperando', 'contactado'], true))
                    @if($manager)
                        <a class="table-action" href="{{ route('citas.index', ['paciente_id'=>$item->paciente_id,'especialidad_id'=>$item->especialidad_id,'medico_id'=>$item->medico_id,'fecha'=>$item->fecha,'accion'=>'consultar']) }}">Consultar turnos</a>
                        <form method="POST" action="{{ route('lista-espera.update', $item->id) }}">@csrf<input type="hidden" name="estado" value="{{ $item->estado === 'esperando' ? 'contactado' : 'esperando' }}"><button class="button secondary">{{ $item->estado === 'esperando' ? 'Marcar contactado' : 'Volver a espera' }}</button></form>
                        <form method="POST" action="{{ route('lista-espera.update', $item->id) }}">@csrf<input type="hidden" name="estado" value="finalizado"><button class="button secondary">Finalizar solicitud</button></form>
                    @endif
                    <form method="POST" action="{{ route('lista-espera.update', $item->id) }}">@csrf<input type="hidden" name="estado" value="cancelado"><button class="button secondary">Cancelar solicitud</button></form>
                @else — @endif
            </div></x-table-cell>
        </tr>
    @empty<x-table-empty>No hay solicitudes que coincidan con estos filtros.</x-table-empty>@endforelse
    </x-responsive-table>
    <div class="admin-pagination">{{ $solicitudes->links() }}</div>
</div>
@endsection
