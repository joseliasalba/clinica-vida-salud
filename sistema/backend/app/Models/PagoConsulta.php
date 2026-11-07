<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PagoConsulta extends Model
{
    use HasFactory;

    protected $table = 'pagos_consulta';

    protected $guarded = ['id'];

    protected $hidden = ['checkout_payload', 'checkout_url'];

    protected function casts(): array
    {
        return ['importe_centavos' => 'integer', 'checkout_payload' => 'array', 'vence_en' => 'immutable_datetime', 'pagado_en' => 'immutable_datetime'];
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(CitaMedica::class, 'cita_id');
    }

    public function reembolsos(): HasMany
    {
        return $this->hasMany(ReembolsoConsulta::class, 'pago_id');
    }

    public function getRouteKeyName(): string
    {
        return 'codigo';
    }

    public function amount(): string
    {
        return intdiv($this->importe_centavos, 100).'.'.str_pad((string) ($this->importe_centavos % 100), 2, '0', STR_PAD_LEFT);
    }

    public function label(): string
    {
        return match ($this->estado) {
            'nuevo' => 'Pendiente de iniciar', 'incierto' => 'Confirmando conexión',
            'pendiente' => 'Pendiente de pago', 'procesando' => 'Pago en proceso',
            'pagado' => 'Pagado', 'vencido' => 'Reserva liberada',
            'revision' => 'Pago recibido: requiere revisión',
            'qr_pendiente' => 'Esperando reporte del pago QR',
            'qr_revision' => 'Pago QR pendiente de revisión',
            'qr_rechazado' => 'Reporte de pago QR rechazado',
            default => 'Revisar estado',
        };
    }
}
