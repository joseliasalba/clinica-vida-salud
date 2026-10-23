<?php

namespace App\Http\Requests\Citas;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class CancelarCitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('citas.gestionar') ?? false;
    }

    /** @return array<string, array<string>> */
    public function rules(): array
    {
        return [
            'motivo' => ['required', 'string', 'max:255'],
            'version' => ['required', 'string', 'size:64'],
            'confirmar' => ['required', 'accepted'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('motivo'))) {
            $this->merge(['motivo' => Str::squish($this->input('motivo'))]);
        }
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'motivo.required' => 'Indica el motivo de la cancelación.',
            'motivo.string' => 'El motivo debe ser un texto.',
            'motivo.max' => 'El motivo no puede superar los 255 caracteres.',
            'version.required' => 'Recarga la cita antes de cancelarla.',
            'version.string' => 'Recarga la cita antes de cancelarla.',
            'version.size' => 'Recarga la cita antes de cancelarla.',
            'confirmar.required' => 'Confirma que deseas cancelar esta cita.',
            'confirmar.accepted' => 'Confirma que deseas cancelar esta cita.',
        ];
    }
}
