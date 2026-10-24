@extends('layouts.citas')
@section('title', 'Comprobante '.$cita->codigo)
@section('booking-title', 'Comprobante de reserva.')
@section('booking-description', 'Conserva este código para identificar tu cita.')
@section('booking-content')
<section class="admin-panel booking-receipt">
    <span class="eyebrow">CLÍNICA VIDA SALUD</span><h2>{{ $cita->codigo }}</h2><span class="badge">Estado: {{ $cita->estado }}</span>
    <dl class="booking-details">
        <div><dt>Paciente</dt><dd>{{ $cita->paciente->nombres }} {{ $cita->paciente->apellidos }} · {{ $cita->paciente->codigo }}</dd></div>
        <div><dt>Especialidad</dt><dd>{{ $cita->especialidad->nombre }}</dd></div>
        <div><dt>Médico</dt><dd>{{ $cita->medico->nombres }} {{ $cita->medico->apellidos }}</dd></div>
        <div><dt>Fecha</dt><dd>{{ $cita->inicio->format('d/m/Y') }}</dd></div>
        <div><dt>Horario de Bolivia</dt><dd>{{ $cita->inicio->format('H:i') }} – {{ $cita->fin->format('H:i') }}</dd></div>
    </dl>
    <p class="panel-description">Presenta tu documento de identidad y este código en recepción. Este comprobante no sustituye la historia clínica.</p>
    <div class="admin-form-footer booking-actions"><a class="button secondary" href="{{ route('citas.index') }}">Consultar disponibilidad</a><button class="button primary" type="button" data-print-receipt hidden>Imprimir / guardar PDF</button></div>
</section>
@endsection