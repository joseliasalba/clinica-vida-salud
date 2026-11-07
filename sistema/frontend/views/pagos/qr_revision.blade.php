@extends('layouts.citas')
@section('title', 'Revisión de pagos QR')
@section('booking-title', 'Revisión de pagos QR.')
@section('booking-description', 'Comprueba cada operación en la cuenta receptora antes de tomar una decisión.')
@section('booking-content')
<section class="admin-panel">
    <h2>QR para recibir pagos</h2>
    <p>{{ $qr ? 'Hay un QR activo para nuevas reservas.' : 'Todavía no hay un QR configurado.' }}</p>
    @if(auth()->user()->tienePermiso('usuarios.gestionar'))
    <form method="POST" action="{{ route('pagos.qr.upload') }}" enctype="multipart/form-data">
        @csrf
        <label for="qr">Imagen del QR (PNG o JPEG, máximo 2 MB)</label>
        <input id="qr" name="qr" type="file" accept="image/png,image/jpeg" required>
        <button class="button primary" type="submit">Guardar QR</button>
    </form>
    <p class="panel-description">Revisa el destinatario antes de cargarlo. El QR anterior se conserva para los pagos ya iniciados.</p>
    @endif
</section>
<section class="admin-panel">
    <h2>Reportes pendientes</h2>
    <p>El número de operación no verifica el pago automáticamente. Confirma el importe y la operación con el banco.</p>
    <x-responsive-table label="Pagos QR pendientes" :columns="['cita'=>'Cita', 'paciente'=>'Paciente', 'importe'=>'Importe', 'referencia'=>'Operación', 'acciones'=>'Revisión']">
        @forelse($pagos as $pago)
        <tr role="row">
            <x-table-cell column="cita" heading>{{ $pago->cita->codigo }}<br>{{ $pago->cita->inicio->format('d/m/Y H:i') }}<br>{{ $pago->cita->estadoLabel() }}</x-table-cell>
            <x-table-cell column="paciente">{{ $pago->cita->paciente->nombres }} {{ $pago->cita->paciente->apellidos }}</x-table-cell>
            <x-table-cell column="importe">Bs {{ $pago->amount() }}</x-table-cell>
            <x-table-cell column="referencia">{{ $pago->referencia_qr }}</x-table-cell>
            <x-table-cell column="acciones">
                <form method="POST" action="{{ route('pagos.qr.decide', $pago) }}">@csrf
                    <button class="button primary" name="decision" value="aprobar" type="submit">Confirmar pago</button>
                </form>
                <form method="POST" action="{{ route('pagos.qr.decide', $pago) }}">@csrf
                    <label for="motivo-{{ $pago->id }}">Motivo del rechazo</label>
                    <input id="motivo-{{ $pago->id }}" name="motivo" maxlength="500" required>
                    <button class="button secondary" name="decision" value="rechazar" type="submit">Rechazar reporte</button>
                </form>
            </x-table-cell>
        </tr>
        @empty
        <x-table-empty>No hay pagos QR pendientes de revisión.</x-table-empty>
        @endforelse
    </x-responsive-table>
    <div class="admin-pagination">{{ $pagos->links() }}</div>
</section>
@endsection
