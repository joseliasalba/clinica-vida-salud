<?php

namespace App\Http\Requests\Horarios;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('horarios.gestionar') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'medico_id' => ['required', 'integer', Rule::exists('medicos', 'id')],
            'dia_semana' => ['required', 'integer', 'between:1,7'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'duracion_turno_min' => ['required', 'integer', 'between:1,480'],
            'vigente_desde' => ['required', 'date_format:Y-m-d'],
            'vigente_hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:vigente_desde'],
            'version' => [$this->route('horario') ? 'required' : 'nullable', 'string', 'size:64'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            [$startHour, $startMinute] = array_map('intval', explode(':', $this->input('hora_inicio')));
            [$endHour, $endMinute] = array_map('intval', explode(':', $this->input('hora_fin')));
            $length = ($endHour - $startHour) * 60 + $endMinute - $startMinute;
            if ($length % (int) $this->input('duracion_turno_min') !== 0) {
                $validator->errors()->add('duracion_turno_min', 'La franja debe contener un número entero de turnos completos.');
            }
            $start = CarbonImmutable::parse($this->input('vigente_desde'));
            $first = $start->addDays(((int) $this->input('dia_semana') - $start->isoWeekday() + 7) % 7);
            if ($this->filled('vigente_hasta') && $first->toDateString() > $this->input('vigente_hasta')) {
                $validator->errors()->add('vigente_hasta', 'La vigencia debe incluir al menos una fecha del día de atención elegido.');
            }
        }];
    }

    public function messages(): array
    {
        return ['required' => 'Completa este campo.', 'integer' => 'Ingresa un número entero válido.', 'medico_id.exists' => 'El médico no existe.', 'between' => 'El valor está fuera del rango permitido.', 'date_format' => 'Ingresa una fecha u hora válida.', 'hora_fin.after' => 'La hora final debe ser posterior a la inicial, dentro del mismo día.', 'vigente_hasta.after_or_equal' => 'La vigencia final no puede ser anterior a la inicial.', 'version.required' => 'Recarga el horario antes de guardar.', 'version.size' => 'Recarga el horario antes de guardar.'];
    }
}
