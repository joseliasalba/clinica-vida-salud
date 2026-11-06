<?php

namespace App\Http\Requests\Estudios;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveStudyResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('estudios.publicar') ?? false;
    }

    public function rules(): array
    {
        return [
            'version' => ['required', 'string', 'size:64'],
            'accion' => ['required', Rule::in(['borrador', 'publicar', 'retirar'])],
            'descripcion' => ['nullable', 'string', 'max:20000'],
            'archivo' => ['nullable', 'file', 'mimes:pdf', 'extensions:pdf', 'max:2048'],
            'motivo' => ['required_if:accion,retirar', 'nullable', 'string', 'max:500'],
            'confirmacion' => ['accepted'],
        ];
    }
}
