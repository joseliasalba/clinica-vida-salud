<?php

namespace App\Http\Requests\Auth;

use App\Rules\PasswordPolicy;
use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'correo' => ['required', 'email', 'max:255'],
            'token' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'confirmed', new PasswordPolicy],
        ];
    }

    public function messages(): array
    {
        return ['required' => 'Completa este campo.', 'email' => 'Ingresa un correo válido.', 'confirmed' => 'Las contraseñas no coinciden.'];
    }
}
