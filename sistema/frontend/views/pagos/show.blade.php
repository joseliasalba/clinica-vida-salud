@extends('layouts.citas')
@section('title', 'Pago de consulta')
@section('booking-title', 'Pago de tu consulta.')
@section('booking-description', 'La cita se confirma cuando verificamos el pago.')
@section('booking-content')
@if($pago->metodo !== 'qr')<div class="notice">Stripe está en modo de prueba. Utiliza únicamente tarjetas de prueba.</div>@endif
<section class="admin-panel">
<h2>{{ $pago->label() }}</h2>
<dl class="booking-details">
<div><dt>Cita</dt><dd>{{ $pago->cita->codigo }}</dd></div>
<div><dt>Especialidad</dt><dd>{{ $pago->cita->especialidad->nombre }}</dd></div>
<div><dt>Fecha y hora</dt><dd>{{ $pago->cita->inicio->format('d/m/Y H:i') }}</dd></div>
<div><dt>Total</dt><dd>Bs {{ $pago->amount() }} (BOB)</dd></div>
<div><dt>Estado de la cita</dt><dd>{{ $pago->cita->estadoLabel() }}</dd></div>
@if(!in_array($pago->estado, ['pagado', 'revision', 'vencido', 'qr_revision', 'qr_rechazado'], true))
<div><dt>Plazo actual</dt><dd>{{ $pago->vence_en->timezone('America/La_Paz')->format('d/m/Y H:i:s') }}</dd></div>
@endif
</dl>
@if($pago->estado === 'pagado')
<p>{{ $pago->metodo === 'qr' ? 'Recepción confirmó el pago QR.' : 'Pago de prueba confirmado.' }} Ya puedes consultar tu comprobante de cita.</p>
@elseif($pago->estado === 'qr_revision')
<p>Recibimos tu número de operación. Recepción debe verificarlo; la cita todavía no está confirmada. No vuelvas a pagar.</p>
<p>Operación: {{ $pago->referencia_qr }}</p>
@elseif($pago->estado === 'qr_rechazado')
<p>Recepción rechazó el reporte. Contacta a la clínica antes de volver a pagar.</p>
@elseif($pago->estado === 'qr_pendiente' || ($pago->estado === 'vencido' && $pago->metodo === 'qr'))
@if($pago->estado === 'vencido')
<p>El cupo fue liberado. No inicies una nueva transferencia para esta cita.</p>
@else
<p>Escanea el QR y transfiere exactamente Bs {{ $pago->amount() }}. El QR puede pertenecer a una cuenta personal de demostración; verifica el destinatario antes de pagar.</p>
@endif
<p>Debes reportar el número de operación. Si el plazo venció o el cupo ya fue liberado, recepción revisará el pago y no se garantiza la cita.</p>
<img src="{{ route('pagos.qr.image', $pago) }}" alt="Código QR para pago de la consulta" style="max-width: 100%; width: 18rem; height: auto">
<form method="POST" action="{{ route('pagos.qr.report', $pago) }}">@csrf
    <label for="referencia">Número de operación</label>
    <input id="referencia" name="referencia" maxlength="100" required value="{{ old('referencia') }}">
    <button class="button primary" type="submit">Informar pago a recepción</button>
</form>
@elseif($pago->estado === 'revision')
<p>Recibimos el pago, pero la cita no pudo confirmarse. Contacta a recepción con el código de cita para revisar la devolución. No vuelvas a pagar.</p>
@elseif($pago->estado === 'vencido')
<p>El cupo se liberó. Consulta nuevamente la disponibilidad. Si realizaste un pago, actualiza este estado para comprobarlo antes de reservar otra vez.</p>
@elseif($pago->estado === 'procesando')
<p>Stripe está procesando el pago. No hagas otro pago. Si no se confirma dentro del plazo, liberaremos el cupo y cualquier pago posterior requerirá revisión.</p>
@else
<p>El cupo está retenido temporalmente. Inicia el pago para abrir Stripe Checkout. Volver de Stripe o cerrar su página no confirma ni cancela el pago.</p>
@if($pago->estado === 'nuevo')<p>Al iniciar Checkout se mostrará su plazo definitivo de aproximadamente 30 minutos.</p>@endif
@endif
<div class="admin-form-footer">
@if($pago->metodo !== 'qr' && in_array($pago->estado, ['nuevo','incierto','pendiente'], true) && $pago->vence_en->isFuture())
<form method="POST" action="{{ route('pagos.start', $pago) }}">@csrf<button class="button primary" type="submit">Continuar a Stripe (prueba)</button></form>
@endif
@if($pago->estado === 'nuevo' && $pago->metodo === null && $pago->vence_en->isFuture() && $qrDisponible)
<form method="POST" action="{{ route('pagos.qr.select', $pago) }}">@csrf<button class="button secondary" type="submit">Pagar por QR</button></form>
@endif
<form method="POST" action="{{ route('pagos.refresh', $pago) }}">@csrf<button class="button secondary" type="submit">Actualizar estado del pago</button></form>
@if($pago->estado === 'pagado')
<a class="button secondary" href="{{ route('citas.receipt', $pago->cita) }}">Comprobante de cita</a>
@endif
@if($pago->stripe_invoice_id && in_array($pago->estado, ['pagado','revision'], true))
<a class="button secondary" href="{{ route('pagos.invoice', $pago) }}">Comprobante Stripe (prueba)</a>
@endif
<a class="button secondary" href="{{ route('pagos.index') }}">Mis pagos</a>
</div>
@if($pago->reembolsos->isNotEmpty() || $puedeSolicitarReembolso || $solicitarQrEnRecepcion)
<section aria-labelledby="devoluciones-titulo">
    <h3 id="devoluciones-titulo">Devolución</h3>
    @foreach($pago->reembolsos as $solicitud)
    <p>{{ $solicitud->label() }} · Bs {{ $solicitud->amount() }} @if($solicitud->decision_motivo) · {{ $solicitud->decision_motivo }} @endif</p>
    @endforeach
    @if($solicitarQrEnRecepcion)
    <p>Para solicitar una devolución de pago QR, contacta a recepción con tu código de cita.</p>
    @endif
    @if($puedeSolicitarReembolso)
    <p>La solicitud será revisada. No implica una devolución automática.</p>
    <form method="POST" action="{{ route('reembolsos.store', $pago) }}">@csrf
        <label for="motivo-reembolso">Motivo de la solicitud</label>
        <textarea id="motivo-reembolso" name="motivo" maxlength="500" required>{{ old('motivo') }}</textarea>
        <button class="button secondary" type="submit">Solicitar devolución</button>
    </form>
    @endif
</section>
@endif
@if($pago->metodo !== 'qr')<p class="panel-description">El comprobante de Stripe es de prueba y no es una factura fiscal boliviana.</p>@endif
</section>
@endsection
