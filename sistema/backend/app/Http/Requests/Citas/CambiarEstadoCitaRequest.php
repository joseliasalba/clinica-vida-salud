<?php

namespace App\Http\Requests\Citas;

use App\Models\CitaMedica;
use App\Services\Citas\CitaWorkflow;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CambiarEstadoCitaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $cita = $this->route('cita');
        if (! $this->user() || ! $cita instanceof CitaMedica) {
            return false;
        }

        app(CitaWorkflow::class)->authorize($this->user(), $cita, true);

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'estado' => ['required', 'string', Rule::in(['confirmada', 'completada', 'inasistencia'])],
            'version' => ['required', 'string', 'size:64'],
            'motivo' => ['nullable', 'string', 'max:255'],
            'confirmacion' => ['required', 'accepted'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'estado.required' => 'Selecciona el nuevo estado de la cita.',
            'estado.in' => 'Selecciona confirmada, atendida o inasistencia.',
            'version.required' => 'Recarga la cita antes de confirmar el cambio.',
            'version.string' => 'Recarga la cita antes de confirmar el cambio.',
            'version.size' => 'Recarga la cita antes de confirmar el cambio.',
            'motivo.string' => 'Escribe una observación válida.',
            'motivo.max' => 'La observación no puede superar los 255 caracteres.',
            'confirmacion.required' => 'Confirma que revisaste el cambio de estado.',
            'confirmacion.accepted' => 'Confirma que revisaste el cambio de estado.',
        ];
    }
}
