@extends('layouts.citas')
@section('title', 'Reservar cita')
@section('booking-title', 'Encuentra tu próximo turno.')
@section('booking-description', 'Elige una especialidad y consulta los horarios disponibles. Consultar un turno no lo aparta.')
@section('booking-content')
<form method="GET" action="{{ route('citas.index') }}" class="admin-editor">
    @if($manager)
    <section class="admin-panel"><h2>Paciente de la reserva</h2><p class="panel-description">Busca una ficha activa por nombre, código o CI. Se muestran hasta 20 coincidencias; precisa la búsqueda si no aparece.</p>
        <div class="field"><label for="paciente_q">Buscar paciente</label><input id="paciente_q" name="paciente_q" maxlength="100" value="{{ $filters['paciente_q'] ?? '' }}"></div>
        <button class="button secondary" type="submit" name="accion" value="buscar_paciente" formnovalidate>Buscar pacientes</button>
        <div class="field"><label for="paciente_id">Seleccionar paciente</label><select id="paciente_id" name="paciente_id"><option value="">Selecciona un paciente</option>@foreach($pacientes as $patient)<option value="{{ $patient->id }}" @selected($paciente?->id === $patient->id)>{{ $patient->codigo }} · {{ $patient->nombres }} {{ $patient->apellidos }}</option>@endforeach</select></div>
        @if($paciente)<p class="booking-selected-patient"><strong>Paciente seleccionado:</strong> {{ $paciente->nombres }} {{ $paciente->apellidos }} · {{ $paciente->codigo }}</p>@endif
        @if(!empty($filters['paciente_q']) && $pacientes->isEmpty())<p>No encontramos pacientes activos con esos datos.</p>@endif
    </section>
    @elseif($paciente)
    <section class="admin-panel"><h2>Tu reserva</h2><p>{{ $paciente->nombres }} {{ $paciente->apellidos }} · {{ $paciente->codigo }}</p></section>
    @else
    <div class="notice">Tu cuenta no tiene una ficha activa vinculada. Solicita ayuda en recepción para poder reservar.</div>
    @endif
    <section class="admin-panel"><h2>Disponibilidad</h2><div class="admin-form-grid">
        <div class="field"><label for="especialidad_id">Especialidad</label><select id="especialidad_id" name="especialidad_id"><option value="">Selecciona una especialidad</option>@foreach($especialidades as $specialty)<option value="{{ $specialty->id }}" @selected((int)($filters['especialidad_id'] ?? 0) === $specialty->id)>{{ $specialty->nombre }}</option>@endforeach</select></div>
        <div class="field"><label for="medico_id">Médico (opcional)</label><select id="medico_id" name="medico_id"><option value="">Todos los médicos de la especialidad</option>@foreach($medicos as $doctor)<option value="{{ $doctor->id }}" @selected((int)($filters['medico_id'] ?? 0) === $doctor->id)>{{ $doctor->nombres }} {{ $doctor->apellidos }}</option>@endforeach</select><small>Puedes dejarlo vacío para ver todas las opciones disponibles.</small></div>
        <div class="field"><label for="fecha">Fecha de atención</label><input type="date" id="fecha" name="fecha" value="{{ $filters['fecha'] ?? now()->toDateString() }}" min="{{ now()->toDateString() }}" max="{{ now()->addDays(config('citas.dias_anticipacion'))->toDateString() }}" required><small>Reservas hasta {{ config('citas.dias_anticipacion') }} días hacia adelante.</small></div>
    </div><button class="button primary" type="submit" name="accion" value="consultar">Consultar turnos</button></section>
</form>
@if(!empty($filters['especialidad_id']) && ($filters['accion'] ?? null) !== 'buscar_paciente')
<section class="booking-results" aria-labelledby="available-title">
    <div class="section-heading"><h2 id="available-title">Turnos disponibles</h2><span>{{ $turnos->count() }} opciones</span></div>
    <div class="booking-slots">
    @forelse($turnos as $turno)
        <article class="admin-panel booking-slot"><h3>{{ substr($turno['inicio'], 11, 5) }} – {{ substr($turno['fin'], 11, 5) }}</h3><p>{{ $turno['medico'] }}</p><p>{{ \Carbon\CarbonImmutable::parse($turno['inicio'])->format('d/m/Y') }}</p><a class="button secondary" href="{{ route('citas.review', ['especialidad_id' => $filters['especialidad_id'], 'paciente_id' => $paciente->id, 'medico_id' => $turno['medico_id'], 'horario_id' => $turno['horario_id'], 'fecha' => substr($turno['inicio'], 0, 10), 'inicio' => $turno['inicio']]) }}" aria-label="Revisar turno de {{ $turno['medico'] }} a las {{ substr($turno['inicio'], 11, 5) }}">Revisar turno</a></article>
    @empty
        <div class="notice">No hay turnos disponibles con estos filtros. Puede no haber atención programada, existir bloqueos o coincidir con citas del paciente. Prueba otra fecha o médico.</div>
    @endforelse
    </div>
</section>
@elseif($especialidades->isEmpty())
<div class="notice">Todavía no hay especialidades activas para reservar. Consulta en recepción.</div>
@endif
@endsection
