<?php

namespace App\Http\Requests\Recordatorios;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveReminderPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('recordatorios.propios') ?? false;
    }

    public function rules(): array
    {
        return [
            'recordatorios_activos' => ['required', 'boolean'],
            'anticipacion_horas' => ['required', 'integer', Rule::in([2, 24, 48])],
            'incluir_detalles' => ['required', 'boolean'],
            'version' => ['required', 'string', 'size:64'],
        ];
    }
}
