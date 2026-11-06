@extends('layouts.citas')
@section('title', 'Pago de consulta')
@section('booking-title', 'Pago de tu consulta.')
@section('booking-description', 'La cita se confirma cuando verificamos el pago.')
@section('booking-content')
<div class="notice">Modo de prueba de Stripe. Utiliza únicamente tarjetas de prueba; no se mueve dinero real.</div>
<section class="admin-panel">
<h2>{{ $pago->label() }}</h2>
<dl class="booking-details">
<div><dt>Cita</dt><dd>{{ $pago->cita->codigo }}</dd></div>
<div><dt>Especialidad</dt><dd>{{ $pago->cita->especialidad->nombre }}</dd></div>
<div><dt>Fecha y hora</dt><dd>{{ $pago->cita->inicio->format('d/m/Y H:i') }}</dd></div>
<div><dt>Total</dt><dd>Bs {{ $pago->amount() }} (BOB)</dd></div>
<div><dt>Estado de la cita</dt><dd>{{ $pago->cita->estadoLabel() }}</dd></div>
@if(!in_array($pago->estado, ['pagado', 'revision', 'vencido'], true))
<div><dt>Plazo actual</dt><dd>{{ $pago->vence_en->timezone('America/La_Paz')->format('d/m/Y H:i:s') }}</dd></div>
@endif
</dl>
@if($pago->estado === 'pagado')
<p>Pago de prueba confirmado. Ya puedes consultar tu comprobante de cita.</p>
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
@if(in_array($pago->estado, ['nuevo','incierto','pendiente'], true) && $pago->vence_en->isFuture())
<form method="POST" action="{{ route('pagos.start', $pago) }}">@csrf<button class="button primary" type="submit">Continuar a Stripe (prueba)</button></form>
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
<p class="panel-description">El comprobante de Stripe es de prueba y no es una factura fiscal boliviana.</p>
</section>
@endsection
