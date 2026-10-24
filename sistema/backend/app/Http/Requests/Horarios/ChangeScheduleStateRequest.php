<?php

namespace App\Http\Requests\Horarios;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ChangeScheduleStateRequest extends FormRequest
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
            'activo' => ['required', 'boolean'], 'version' => ['required', 'string', 'size:64'],
        ];
    }

    public function messages(): array
    {
        return ['activo.required' => 'Selecciona un estado.', 'activo.boolean' => 'El estado no es válido.', 'version.required' => 'Recarga la confirmación antes de guardar.', 'version.string' => 'Recarga la confirmación antes de guardar.', 'version.size' => 'Recarga la confirmación antes de guardar.'];
    }
}
