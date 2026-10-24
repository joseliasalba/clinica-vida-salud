<?php

namespace App\Http\Requests\Citas;

use Illuminate\Foundation\Http\FormRequest;

class BookAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('citas.gestionar') || $this->user()?->tienePermiso('citas.propias');
    }

    public function rules(): array
    {
        return ['token' => ['required', 'string', 'max:8192']];
    }
}
