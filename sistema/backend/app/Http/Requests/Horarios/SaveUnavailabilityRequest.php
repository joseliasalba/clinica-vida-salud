<?php

namespace App\Http\Requests\Horarios;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveUnavailabilityRequest extends FormRequest
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
            'inicio' => ['required', 'date_format:Y-m-d\TH:i'],
            'fin' => ['required', 'date_format:Y-m-d\TH:i', 'after:inicio'],
            'motivo' => ['required', 'string', 'max:255'],
            'version' => [$this->route('bloqueo') ? 'required' : 'nullable', 'string', 'size:64'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('motivo'))) {
            $this->merge(['motivo' => trim($this->input('motivo'))]);
        }
    }

    public function messages(): array
    {
        return ['required' => 'Completa este campo.', 'integer' => 'Selecciona un médico válido.', 'medico_id.exists' => 'El médico no existe.', 'date_format' => 'Ingresa fecha y hora válidas.', 'fin.after' => 'El final debe ser posterior al inicio.', 'motivo.string' => 'Ingresa un motivo válido.', 'motivo.max' => 'El motivo no puede superar 255 caracteres.', 'version.required' => 'Recarga el bloqueo antes de guardar.', 'version.size' => 'Recarga el bloqueo antes de guardar.'];
    }
}
