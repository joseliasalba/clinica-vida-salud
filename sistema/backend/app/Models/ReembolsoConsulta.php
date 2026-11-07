<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReembolsoConsulta extends Model
{
    use HasFactory;

    protected $table = 'reembolsos_consulta';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['importe_centavos' => 'integer', 'iniciado_en' => 'immutable_datetime', 'completado_en' => 'immutable_datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'codigo';
    }

    public function pago(): BelongsTo
    {
        return $this->belongsTo(PagoConsulta::class, 'pago_id');
    }

    public function amount(): string
    {
        return intdiv($this->importe_centavos, 100).'.'.str_pad((string) ($this->importe_centavos % 100), 2, '0', STR_PAD_LEFT);
    }

    public function label(): string
    {
        return match ($this->estado) {
            'solicitado' => 'Pendiente de revisión',
            'aprobado' => 'Aprobado, devolución pendiente',
            'procesando' => 'Devolución en proceso',
            'completado' => 'Devolución completada',
            'rechazado' => 'Solicitud rechazada',
            'fallido' => 'Devolución fallida',
            'revision' => 'Requiere revisión manual',
            default => 'Revisar estado',
        };
    }
}
