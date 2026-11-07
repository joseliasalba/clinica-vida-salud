@extends('layouts.citas')
@section('title', 'Mis pagos')
@section('booking-title', 'Mis pagos.')
@section('booking-description', 'Consulta el pago y la confirmación de tus citas.')
@section('booking-content')
<div class="notice">Stripe es de prueba. Los pagos QR dependen de la cuenta receptora y requieren revisión en recepción.</div>
<x-responsive-table label="Pagos de mis citas" :columns="['cita'=>'Cita', 'importe'=>'Importe', 'estado'=>'Estado', 'acciones'=>'Acciones']">
@forelse($pagos as $pago)
<tr role="row">
<x-table-cell column="cita" heading>{{ $pago->cita->codigo }}<br>{{ $pago->cita->inicio->format('d/m/Y H:i') }}</x-table-cell>
<x-table-cell column="importe">Bs {{ $pago->amount() }}</x-table-cell>
<x-table-cell column="estado">{{ $pago->label() }}</x-table-cell>
<x-table-cell column="acciones"><a class="table-action" href="{{ route('pagos.show', $pago) }}">Ver pago</a></x-table-cell>
</tr>
@empty
<x-table-empty>Aún no tienes pagos de consultas.</x-table-empty>
@endforelse
</x-responsive-table>
<div class="admin-pagination">{{ $pagos->links() }}</div>
@endsection
