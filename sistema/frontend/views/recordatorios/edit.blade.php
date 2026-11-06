@extends('layouts.app')
@section('title', 'Preferencias y recordatorios')
@section('content')
@php
    $value = static fn (string $field, mixed $default): string => is_scalar(old($field, $default)) ? (string)old($field, $default) : '';
@endphp
<div class="admin-shell">
    <div class="admin-breadcrumb"><a href="{{ route('inicio') }}">← Mi inicio</a><span>Mi cuenta</span></div>
    <header class="admin-heading"><div><span class="eyebrow">MI CUENTA · VIDA SALUD</span><h1>Tus recordatorios, a tu manera.</h1><p>Elige si quieres recibir un recordatorio por correo antes de tus citas.</p></div></header>
    <x-messages />
    @if($errors->has('version'))<div class="notice"><a href="{{ route('recordatorios.edit') }}">Recargar mis preferencias</a> antes de guardar nuevamente.</div>@endif
    <form class="admin-editor" method="POST" action="{{ route('recordatorios.update') }}">
        @csrf @method('PUT')
        <input type="hidden" name="version" value="{{ $value('version', $version) }}">
        <section class="admin-panel"><h2>Recordatorios de citas</h2>
            <p class="panel-description">Se enviarán al correo verificado de tu cuenta: <strong>{{ auth()->user()->correo }}</strong>. Este ajuste no cambia los mensajes de verificación o recuperación de contraseña.</p>
            <div class="admin-form-grid">
                <div class="field"><label for="recordatorios_activos">¿Recibir recordatorios por correo?</label><select id="recordatorios_activos" name="recordatorios_activos" required><option value="0" @selected($value('recordatorios_activos', (int)($preferences?->recordatorios_activos ?? false)) === '0')>No recibir recordatorios</option><option value="1" @selected($value('recordatorios_activos', (int)($preferences?->recordatorios_activos ?? false)) === '1')>Sí, recibir recordatorios</option></select></div>
                <div class="field"><label for="anticipacion_horas">Anticipación del recordatorio</label><select id="anticipacion_horas" name="anticipacion_horas" required>@foreach([2,24,48] as $hours)<option value="{{ $hours }}" @selected($value('anticipacion_horas', $preferences?->anticipacion_horas ?? 24) === (string)$hours)>{{ $hours }} horas antes</option>@endforeach</select></div>
            </div>
            <p class="panel-description">Si activas los recordatorios cuando falta menos tiempo que el seleccionado, se intentará enviar uno en la siguiente ejecución del proceso, antes del inicio de la cita. Las citas canceladas o que ya comenzaron se omiten.</p>
        </section>
        <section class="admin-panel"><h2>Contenido del correo</h2><div class="field"><label for="incluir_detalles">Información que deseas incluir</label><select id="incluir_detalles" name="incluir_detalles" required><option value="0" @selected($value('incluir_detalles', (int)$details) === '0')>Solo fecha, hora y enlace de acceso</option><option value="1" @selected($value('incluir_detalles', (int)$details) === '1')>Incluir también médico y especialidad</option></select><small>Los recordatorios no incluyen diagnósticos, tratamientos ni resultados de laboratorio. Todos los horarios corresponden a Bolivia.</small></div></section>
        <div class="admin-form-footer"><button class="button primary" type="submit">Guardar mis preferencias</button></div>
    </form>
    <div class="admin-result-heading"><h2>Historial de recordatorios</h2><span>{{ $history->total() }} registros</span></div>
    <p class="panel-description">La aceptación del servicio de correo no confirma la llegada a tu bandeja. Los intentos fallidos o sin confirmar no se reenvían automáticamente para evitar duplicados.</p>
    <x-responsive-table label="Mis recordatorios de citas" :columns="['cita' => 'Cita', 'inicio' => 'Fecha de la cita', 'programado' => 'Programado para', 'estado' => 'Estado', 'envio' => 'Fecha de procesamiento']">
        @forelse($history as $item)<tr role="row">
            <x-table-cell column="cita" heading>{{ $item->codigo }}</x-table-cell>
            <x-table-cell column="inicio">{{ \Illuminate\Support\Carbon::parse($item->inicio_cita)->format('d/m/Y H:i') }}</x-table-cell>
            <x-table-cell column="programado">{{ \Illuminate\Support\Carbon::parse($item->fecha_programada)->format('d/m/Y H:i') }}</x-table-cell>
            <x-table-cell column="estado">{{ \App\Services\Recordatorios\ReminderManagement::ESTADOS[$item->estado] ?? 'Estado no disponible' }}</x-table-cell>
            <x-table-cell column="envio">{{ $item->fecha_envio ? \Illuminate\Support\Carbon::parse($item->fecha_envio)->format('d/m/Y H:i') : 'Sin envío confirmado' }}</x-table-cell>
        </tr>@empty<x-table-empty><div class="admin-empty"><h3>Aún no hay recordatorios registrados.</h3><p>Si los activas, aparecerán aquí cuando el proceso programe tus próximas citas.</p></div></x-table-empty>@endforelse
    </x-responsive-table>
    <div class="admin-pagination">{{ $history->links() }}</div>
</div>
@endsection
