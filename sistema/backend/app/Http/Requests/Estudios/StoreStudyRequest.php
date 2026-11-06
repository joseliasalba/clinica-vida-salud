<?php

namespace App\Http\Requests\Estudios;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('estudios.solicitar') ?? false;
    }

    public function rules(): array
    {
        return [
            'consulta_id' => ['required', 'integer', 'min:1'],
            'nombre_estudio' => ['required', 'string', 'max:255'],
            'indicaciones' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
