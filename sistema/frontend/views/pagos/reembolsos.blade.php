@extends('layouts.citas')
@section('title', 'Reembolsos')
@section('booking-title', 'Solicitudes de devolución.')
@section('booking-description', 'Revisa la solicitud antes de aprobar cualquier devolución.')
@section('booking-content')
<section class="admin-panel">
    <h2>Pagos que pueden solicitar devolución</h2>
    <p>Una cita cancelada no genera una devolución automática. La solicitud es por el importe completo y debe revisarla un administrador.</p>
    <x-responsive-table label="Pagos elegibles para devolución" :columns="['cita'=>'Cita', 'paciente'=>'Paciente', 'importe'=>'Importe', 'solicitud'=>'Solicitud']">
        @forelse($pagos as $pago)
        <tr role="row">
            <x-table-cell column="cita" heading>{{ $pago->cita->codigo }} · {{ $pago->cita->estadoLabel() }}</x-table-cell>
            <x-table-cell column="paciente">{{ $pago->cita->paciente->nombres }} {{ $pago->cita->paciente->apellidos }}</x-table-cell>
            <x-table-cell column="importe">Bs {{ $pago->amount() }} · {{ $pago->metodo === 'qr' ? 'QR' : 'Stripe de prueba' }}</x-table-cell>
            <x-table-cell column="solicitud">
                <form method="POST" action="{{ route('reembolsos.store', $pago) }}">@csrf
                    <label for="solicitud-{{ $pago->id }}">Motivo</label>
                    <input id="solicitud-{{ $pago->id }}" name="motivo" maxlength="500" required>
                    <button class="button secondary" type="submit">Registrar solicitud</button>
                </form>
            </x-table-cell>
        </tr>
        @empty
        <x-table-empty>No hay pagos disponibles para solicitar devolución.</x-table-empty>
        @endforelse
    </x-responsive-table>
    <div class="admin-pagination">{{ $pagos->links() }}</div>
</section>
<section class="admin-panel">
    <h2>Solicitudes</h2>
    <x-responsive-table label="Solicitudes de devolución" :columns="['cita'=>'Cita', 'importe'=>'Importe', 'motivo'=>'Motivo', 'estado'=>'Estado', 'acciones'=>'Acciones']">
        @forelse($solicitudes as $solicitud)
        <tr role="row">
            <x-table-cell column="cita" heading>{{ $solicitud->pago->cita->codigo }}<br>{{ $solicitud->pago->cita->paciente->nombres }} {{ $solicitud->pago->cita->paciente->apellidos }}</x-table-cell>
            <x-table-cell column="importe">Bs {{ $solicitud->amount() }}<br>{{ $solicitud->pago->metodo === 'qr' ? 'QR' : 'Stripe de prueba' }}</x-table-cell>
            <x-table-cell column="motivo">{{ $solicitud->motivo }} @if($solicitud->decision_motivo)<br>Decisión: {{ $solicitud->decision_motivo }}@endif</x-table-cell>
            <x-table-cell column="estado">{{ $solicitud->label() }} @if($solicitud->referencia_devolucion)<br>Operación: {{ $solicitud->referencia_devolucion }}@endif</x-table-cell>
            <x-table-cell column="acciones">
                @if($esAdmin && $solicitud->estado === 'solicitado')
                <form method="POST" action="{{ route('reembolsos.decide', $solicitud) }}">@csrf
                    <button class="button primary" name="decision" value="aprobar" type="submit">Aprobar solicitud</button>
                </form>
                <form method="POST" action="{{ route('reembolsos.decide', $solicitud) }}">@csrf
                    <label for="rechazo-{{ $solicitud->id }}">Motivo del rechazo</label>
                    <input id="rechazo-{{ $solicitud->id }}" name="motivo" maxlength="500" required>
                    <button class="button secondary" name="decision" value="rechazar" type="submit">Rechazar solicitud</button>
                </form>
                @elseif($esAdmin && $solicitud->pago->metodo === 'qr' && $solicitud->estado === 'aprobado')
                <form method="POST" action="{{ route('reembolsos.manual', $solicitud) }}">@csrf
                    <p>Realiza la devolución fuera del sistema antes de registrarla aquí.</p>
                    <label for="devolucion-{{ $solicitud->id }}">Número de operación de devolución</label>
                    <input id="devolucion-{{ $solicitud->id }}" name="referencia" maxlength="100" required>
                    <label><input type="checkbox" name="confirmar" value="1" required> Confirmo que se realizó la devolución bancaria.</label>
                    <button class="button primary" type="submit">Registrar devolución manual</button>
                </form>
                @elseif($esAdmin && $solicitud->pago->metodo !== 'qr' && in_array($solicitud->estado, ['aprobado', 'procesando'], true))
                <form method="POST" action="{{ route('reembolsos.stripe', $solicitud) }}">@csrf
                    <button class="button primary" type="submit">{{ $solicitud->estado === 'aprobado' ? 'Iniciar devolución Stripe de prueba' : 'Consultar devolución Stripe' }}</button>
                </form>
                @else
                <span>—</span>
                @endif
            </x-table-cell>
        </tr>
        @empty
        <x-table-empty>No hay solicitudes de devolución.</x-table-empty>
        @endforelse
    </x-responsive-table>
    <div class="admin-pagination">{{ $solicitudes->links() }}</div>
</section>
@endsection
