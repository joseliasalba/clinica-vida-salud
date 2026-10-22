@extends('layouts.horarios')
@section('title', 'Cambiar estado de '.$tipo)
@section('schedule-title', 'Revisa el cambio en la agenda.')
@section('schedule-description', 'La confirmación conserva el registro y sus referencias.')
@section('schedule-content')
@php($prefix = $tipo === 'horario' ? 'horarios' : 'horarios.bloqueos')
@if($errors->has('version'))<div class="notice"><a href="{{ route($prefix.'.estado.edit', $registro) }}">Recargar la confirmación actualizada</a>.</div>@endif
<form class="admin-editor" method="POST" action="{{ route($prefix.'.estado.update', $registro) }}">
    @csrf @method('PATCH')<input type="hidden" name="version" value="{{ $registro->editVersion() }}"><input type="hidden" name="activo" value="{{ $registro->activo ? '0' : '1' }}">
    <section class="admin-panel"><h2>{{ $registro->medico->nombres }} {{ $registro->medico->apellidos }}</h2>
        @if($tipo === 'horario')<p class="panel-description">{{ \App\Models\HorarioAtencion::DAYS[$registro->dia_semana] }} · {{ substr($registro->hora_inicio, 0, 5) }} – {{ substr($registro->hora_fin, 0, 5) }} · {{ $registro->duracion_turno_min }} minutos por turno.</p>@else<p class="panel-description">{{ $registro->inicio->format('d/m/Y H:i') }} – {{ $registro->fin->format('d/m/Y H:i') }} · {{ $registro->motivo }}</p>@endif
        <p class="panel-description">El {{ $tipo }} quedará <strong>{{ $registro->activo ? 'inactivo' : 'activo' }}</strong>. La operación se rechazará si genera una superposición o afecta citas pendientes. No se cambiará el estado del médico ni se eliminará información.</p>
    </section>
    <div class="admin-form-footer"><a class="button secondary" href="{{ route($prefix.'.index') }}">Cancelar</a><button class="button primary" type="submit">{{ $registro->activo ? 'Confirmar desactivación' : 'Confirmar activación' }}</button></div>
</form>
@endsection
