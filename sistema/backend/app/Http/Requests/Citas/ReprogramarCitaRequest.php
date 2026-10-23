<?php

namespace App\Http\Requests\Citas;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReprogramarCitaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('citas.gestionar') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'version' => ['required', 'string', 'size:64'],
            'medico_id' => ['required', 'integer', 'min:1'],
            'horario_id' => ['required', 'integer', 'min:1'],
            'fecha' => ['required', 'date_format:Y-m-d'],
            'inicio' => ['required', 'date_format:H:i'],
            'turno' => ['nullable', 'string', 'regex:/^[1-9][0-9]*\|[0-2][0-9]:[0-5][0-9]$/'],
            'motivo' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('turno')) && preg_match('/^([1-9][0-9]*)\|([0-2][0-9]:[0-5][0-9])$/', $this->input('turno'), $matches) === 1) {
            $this->merge(['horario_id' => $matches[1], 'inicio' => $matches[2]]);
        }
        if (is_string($this->input('motivo'))) {
            $this->merge(['motivo' => trim($this->input('motivo')) ?: null]);
        }
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'version.required' => 'Recarga la cita antes de guardar los cambios.',
            'version.size' => 'Recarga la cita antes de guardar los cambios.',
            'medico_id.required' => 'Selecciona un médico.',
            'horario_id.required' => 'Selecciona uno de los turnos disponibles.',
            'fecha.required' => 'Selecciona la nueva fecha.',
            'fecha.date_format' => 'Ingresa una fecha válida.',
            'inicio.required' => 'Selecciona uno de los turnos disponibles.',
            'inicio.date_format' => 'Selecciona una hora válida.',
            'motivo.max' => 'El motivo no puede superar los 255 caracteres.',
        ];
    }
}
