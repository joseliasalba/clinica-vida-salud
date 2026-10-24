<?php

namespace App\Http\Requests\Medicos;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ChangeDoctorStateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('medicos.gestionar') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'activo' => ['required', 'boolean'], 'version' => ['required', 'string', 'size:64'],
        ];
    }

    public function messages(): array
    {
        return ['activo.required' => 'Selecciona un estado.', 'activo.boolean' => 'El estado no es válido.', 'version.required' => 'Recarga la ficha antes de confirmar.', 'version.string' => 'Recarga la ficha antes de confirmar.', 'version.size' => 'Recarga la ficha antes de confirmar.'];
    }
}
