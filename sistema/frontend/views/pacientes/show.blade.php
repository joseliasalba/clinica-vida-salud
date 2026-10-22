@extends('layouts.pacientes')
@section('title', 'Ficha del paciente')
@section('patient-title'){{ $paciente->nombres }} {{ $paciente->apellidos }}@endsection
@section('patient-description')Ficha administrativa · {{ $paciente->codigo }}@endsection
@section('patient-actions')<a class="button primary" href="{{ route('pacientes.edit', $paciente) }}">Editar ficha ↗</a>@endsection
@section('patient-content')
<section class="admin-panel"><div class="patient-detail-heading"><h2>Datos del paciente</h2><span class="state {{ $paciente->activo ? 'active' : 'inactive' }}">{{ $paciente->activo ? 'Activo' : 'Inactivo' }}</span></div>
<dl class="patient-details">
    @foreach(['Código' => $paciente->codigo, 'CI' => $paciente->ci ?: 'Sin CI', 'Nombres' => $paciente->nombres, 'Apellidos' => $paciente->apellidos, 'Fecha de nacimiento' => $paciente->fecha_nacimiento->format('d/m/Y'), 'Sexo' => $paciente->sexo ?: 'No registrado', 'Teléfono' => $paciente->telefono, 'Correo' => $paciente->correo ?: 'No registrado', 'Dirección' => $paciente->direccion ?: 'No registrada', 'Registrado el' => $paciente->created_at?->format('d/m/Y H:i') ?: 'No disponible', 'Actualizado el' => $paciente->updated_at?->format('d/m/Y H:i') ?: 'No disponible'] as $label => $value)
    <div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
    @endforeach
</dl></section>
<div class="admin-form-footer"><a class="button secondary" href="{{ route('pacientes.index') }}">Volver a pacientes</a></div>
@endsection
