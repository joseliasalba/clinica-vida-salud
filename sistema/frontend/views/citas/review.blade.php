@extends('layouts.citas')
@section('title', 'Revisar reserva')
@section('booking-title', 'Revisa tu reserva.')
@section('booking-description', 'Comprueba los datos antes de reservar. El turno se verificará nuevamente al guardar.')
@section('booking-content')
<section class="admin-panel booking-receipt">
    <h2>Datos de la cita</h2>
    <dl class="booking-details">
        <div><dt>Paciente</dt><dd>{{ $paciente->nombres }} {{ $paciente->apellidos }} · {{ $paciente->codigo }}</dd></div>
        <div><dt>Especialidad</dt><dd>{{ $especialidad->nombre }}</dd></div>
        <div><dt>Médico</dt><dd>{{ $turno['medico'] }}</dd></div>
        <div><dt>Fecha</dt><dd>{{ \Carbon\CarbonImmutable::parse($turno['inicio'])->format('d/m/Y') }}</dd></div>
        <div><dt>Horario</dt><dd>{{ substr($turno['inicio'], 11, 5) }} – {{ substr($turno['fin'], 11, 5) }}</dd></div>
    </dl>
    <p class="panel-description">La revisión vence en {{ config('citas.minutos_revision') }} minutos. No constituye una reserva hasta que pulses el botón y recibas tu comprobante.</p>
    <form method="POST" action="{{ route('citas.store') }}">@csrf<input type="hidden" name="token" value="{{ $token }}"><div class="admin-form-footer"><a class="button secondary" href="{{ route('citas.index', ['paciente_id' => $paciente->id, 'especialidad_id' => $especialidad->id, 'fecha' => substr($turno['inicio'], 0, 10)]) }}">Volver a disponibilidad</a><button class="button primary" type="submit">Reservar cita</button></div></form>
</section>
@endsection