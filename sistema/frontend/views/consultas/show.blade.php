@extends('layouts.app')
@section('title', 'Consulta médica')
@section('content')
<div class="admin-page">
    <a href="{{ route('consultas.index') }}">← Volver a consultas</a>
    <h1>Consulta médica</h1>
    <p>{{ $appointment->paciente->nombres }} {{ $appointment->paciente->apellidos }} · Cita {{ $appointment->codigo }}</p>
    <x-messages />
    @php($texts = ['motivo_consulta' => 'Motivo de consulta', 'sintomas' => 'Síntomas', 'evaluacion' => 'Evaluación clínica', 'observaciones' => 'Observaciones'])
    @php($numbers = ['peso_kg' => ['Peso (kg)', '0.01'], 'talla_cm' => ['Talla (cm)', '0.01'], 'presion_sistolica' => ['Presión sistólica', '1'], 'presion_diastolica' => ['Presión diastólica', '1'], 'temperatura_c' => ['Temperatura (°C)', '0.1'], 'escala_dolor' => ['Dolor (0–10)', '1']])
    @if($consulta)
        <h2>Registro de atención</h2>
        <dl>
            @foreach($texts as $field => $label)
                <dt>{{ $label }}</dt><dd>{{ $consulta->{$field} ?? 'No registrado' }}</dd>
            @endforeach
            @foreach($numbers as $field => [$label, $step])
                <dt>{{ $label }}</dt><dd>{{ $consulta->{$field} ?? 'No registrado' }}</dd>
            @endforeach
        </dl>
        <h2>Tratamiento</h2>
        @forelse($tratamientos as $tratamiento)
            <p>{{ $tratamiento->indicaciones }}</p>
            <p>Inicio: {{ $tratamiento->fecha_inicio ?? 'Sin fecha' }} · Fin: {{ $tratamiento->fecha_fin ?? 'Sin fecha' }}</p>
        @empty
            <p>No se indicó tratamiento.</p>
        @endforelse
        @can('estudios.solicitar')
            <a class="button primary" href="{{ route('estudios.create', ['consulta_id' => $consulta->id]) }}">Solicitar estudio</a>
        @endcan
    @else
        <form method="POST" action="{{ route('consultas.store', $appointment->id) }}">
            @csrf
            <fieldset class="form-grid">
                <legend>Datos de atención</legend>
                @foreach($texts as $field => $label)
                    <div class="field"><label for="{{ $field }}">{{ $label }}</label><textarea id="{{ $field }}" name="{{ $field }}" maxlength="{{ $field === 'motivo_consulta' ? 2000 : 4000 }}" @required($field === 'evaluacion')>{{ old($field) }}</textarea></div>
                @endforeach
                @foreach($numbers as $field => [$label, $step])
                    <div class="field"><label for="{{ $field }}">{{ $label }}</label><input id="{{ $field }}" name="{{ $field }}" type="number" step="{{ $step }}" min="0" value="{{ old($field) }}"></div>
                @endforeach
            </fieldset>
            <fieldset class="form-grid">
                <legend>Tratamiento</legend>
                <div class="field"><label for="indicaciones">Indicaciones</label><textarea id="indicaciones" name="indicaciones" maxlength="4000">{{ old('indicaciones') }}</textarea></div>
                <div class="field"><label for="fecha_inicio">Fecha de inicio</label><input id="fecha_inicio" name="fecha_inicio" type="date" value="{{ old('fecha_inicio') }}"></div>
                <div class="field"><label for="fecha_fin">Fecha de fin</label><input id="fecha_fin" name="fecha_fin" type="date" value="{{ old('fecha_fin') }}"></div>
            </fieldset>
            <button class="button primary" type="submit">Registrar consulta</button>
        </form>
    @endif
</div>
@endsection
