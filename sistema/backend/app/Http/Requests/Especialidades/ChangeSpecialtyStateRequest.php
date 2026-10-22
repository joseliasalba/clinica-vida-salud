<?php

namespace App\Http\Requests\Especialidades;

use Illuminate\Foundation\Http\FormRequest;

class ChangeSpecialtyStateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('medicos.gestionar') ?? false;
    }

    /** @return array<string, array<string>> */
    public function rules(): array
    {
        return ['activo' => ['required', 'boolean'], 'version' => ['required', 'string', 'size:64']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'activo.required' => 'Selecciona el estado de la especialidad.',
            'activo.boolean' => 'El estado de la especialidad no es válido.',
            'version.required' => 'Recarga la especialidad antes de cambiar su estado.',
            'version.string' => 'Recarga la especialidad antes de cambiar su estado.',
            'version.size' => 'Recarga la especialidad antes de cambiar su estado.',
        ];
    }
}
