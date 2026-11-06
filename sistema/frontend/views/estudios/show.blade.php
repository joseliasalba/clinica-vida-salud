@extends('layouts.estudios')
@section('title', 'Detalle del estudio')
@section('study-title', $estudio->nombre_estudio)
@section('study-description', 'Solicitud #'.$estudio->id.' · Consulta #'.$estudio->consulta_id)
@section('study-content')
@php
    $resultado = $estudio->resultado;
    $version = $estudio->editVersion();
    $text = static fn (string $field, ?string $fallback = null): string => is_string(old($field, $fallback)) ? old($field, $fallback) : '';
@endphp
<div class="admin-editor">
<section class="admin-panel">
    <h2>Solicitud del estudio</h2>
    <p>Paciente: <strong>{{ $consulta->nombres }} {{ $consulta->apellidos }}</strong> · {{ $consulta->codigo }}</p>
    <p>Cita: {{ $consulta->cita_codigo }}</p>
    <p>Estado: <strong>{{ \App\Models\SolicitudEstudio::ESTADOS[$estudio->estado] }}</strong></p>
    <p>Solicitado el {{ $estudio->fecha_solicitud->format('d/m/Y H:i') }} (hora de Bolivia).</p>
    @if($estudio->indicaciones)<h3>Indicaciones</h3><p>{!! nl2br(e($estudio->indicaciones)) !!}</p>@endif
</section>
<section class="admin-panel">
    <h2>Resultado {{ $doctor && $resultado ? '· '.ucfirst($resultado->estado_publicacion) : '' }}</h2>
    @if($resultado)
        @if($resultado->descripcion)<p>{!! nl2br(e($resultado->descripcion)) !!}</p>@endif
        @if($resultado->estado_publicacion === 'publicado')<p>Publicado el {{ $resultado->fecha_publicacion->format('d/m/Y H:i') }} (hora de Bolivia).</p>@endif
        @if($resultado->archivo_privado)<a class="button secondary" href="{{ route('estudios.archivo', $estudio) }}">Descargar resultado PDF</a>@endif
    @else<p class="panel-description">Todavía no hay un resultado publicado para este estudio.</p>@endif
</section>
@if($doctor)
@if($errors->has('version'))<div class="notice"><a href="{{ route('estudios.show', $estudio) }}">Recargar el estudio actualizado</a>. Conserva tu texto antes de recargar.</div>@endif
@can('estudios.publicar')
@if(in_array($estudio->estado, ['solicitado', 'en_proceso'], true))
<form class="admin-panel" method="POST" enctype="multipart/form-data" action="{{ route('estudios.resultado', $estudio) }}">
    @csrf @method('PUT')
    <input type="hidden" name="version" value="{{ $text('version', $version) }}">
    <h2>Cargar resultado</h2><p class="panel-description">El borrador es privado. Al publicar, el paciente podrá consultar el resultado. Los resultados publicados deben retirarse antes de corregirse.</p>
    <div class="field"><label for="descripcion">Descripción del resultado</label><textarea id="descripcion" name="descripcion" maxlength="20000" rows="7">{{ $text('descripcion', $resultado?->descripcion) }}</textarea></div>
    <div class="field"><label for="archivo">Adjuntar o reemplazar PDF (opcional, máximo 2 MB)</label><input id="archivo" name="archivo" type="file" accept=".pdf,application/pdf"><small>Incluye una descripción o un PDF. Si no adjuntas otro archivo, se conserva el actual.</small></div>
    <label class="choice inline"><input type="checkbox" name="confirmacion" value="1" required><span>Confirmo que revisé el contenido y corresponde a este paciente.</span></label>
    <div class="admin-form-footer"><button class="button secondary" type="submit" name="accion" value="borrador">Guardar borrador</button><button class="button primary" type="submit" name="accion" value="publicar">Publicar resultado</button></div>
</form>
@endif
@if($resultado?->estado_publicacion === 'publicado')
<form class="admin-panel" method="POST" action="{{ route('estudios.resultado', $estudio) }}">
    @csrf @method('PUT')<input type="hidden" name="version" value="{{ $text('version', $version) }}"><input type="hidden" name="accion" value="retirar">
    <h2>Retirar resultado</h2><p class="panel-description">El paciente dejará de verlo. El estudio volverá a estar en proceso para preparar una corrección.</p>
    <div class="field"><label for="motivo_retiro">Motivo del retiro</label><textarea id="motivo_retiro" name="motivo" maxlength="500" rows="3" required>{{ $text('motivo') }}</textarea></div>
    <label class="choice inline"><input type="checkbox" name="confirmacion" value="1" required><span>Confirmo el retiro del resultado publicado.</span></label>
    <div class="admin-form-footer"><button class="button secondary" type="submit">Retirar resultado</button></div>
</form>
@endif
@endcan
@can('estudios.solicitar')
@if(in_array($estudio->estado, ['solicitado', 'en_proceso'], true))
<form class="admin-panel" method="POST" action="{{ route('estudios.estado', $estudio) }}">
    @csrf @method('PATCH')<input type="hidden" name="version" value="{{ $text('version', $version) }}">
    <h2>Seguimiento de la solicitud</h2>
    <div class="field"><label for="estado">Cambiar estado</label><select id="estado" name="estado" required>@if($estudio->estado === 'solicitado')<option value="en_proceso">En proceso</option>@endif<option value="cancelado">Cancelado</option></select></div>
    <div class="field"><label for="motivo_estado">Motivo (obligatorio para cancelar)</label><textarea id="motivo_estado" name="motivo" maxlength="500" rows="3">{{ $text('motivo') }}</textarea></div>
    <label class="choice inline"><input type="checkbox" name="confirmacion" value="1" required><span>Confirmo el cambio del estado del estudio.</span></label>
    <div class="admin-form-footer"><button class="button secondary" type="submit">Actualizar estado</button></div>
</form>
@endif
@endcan
@endif
</div>
@endsection
