<?php

namespace App\Http\Requests\Citas;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FindAppointmentRequest extends FormRequest
{
    protected function getRedirectUrl(): string
    {
        return route('citas.index');
    }

    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('citas.gestionar') || $this->user()?->tienePermiso('citas.propias');
    }

    public function rules(): array
    {
        $required = $this->routeIs('citas.review') ? 'required' : 'nullable';

        return [
            'accion' => ['nullable', Rule::in(['buscar_paciente', 'consultar'])],
            'paciente_id' => ['nullable', 'integer', 'min:1'],
            'paciente_q' => ['nullable', 'string', 'max:100'],
            'especialidad_id' => [$required, 'required_if:accion,consultar', 'integer', Rule::exists('especialidades', 'id')],
            'medico_id' => [$required, 'integer', Rule::exists('medicos', 'id')],
            'fecha' => [$required, 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:'.now()->addDays(config('citas.dias_anticipacion'))->toDateString()],
            'horario_id' => [$required, 'integer', 'min:1'],
            'inicio' => [$required, 'date_format:Y-m-d H:i:s'],
        ];
    }

    public function messages(): array
    {
        return [
            'especialidad_id.required_if' => 'Selecciona una especialidad para consultar los turnos.',
            'fecha.after_or_equal' => 'La fecha de atención no puede estar en el pasado.',
            'fecha.before_or_equal' => 'La fecha supera el plazo permitido para reservar.',
        ];
    }
}
