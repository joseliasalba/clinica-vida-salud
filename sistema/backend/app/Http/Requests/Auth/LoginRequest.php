<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['identificador' => ['required', 'string', 'max:255'], 'password' => ['required', 'string', 'max:1024']];
    }

    public function messages(): array
    {
        return ['required' => 'Completa este campo.', 'max' => 'El valor ingresado es demasiado largo.'];
    }

    public function throttleKey(): string
    {
        return 'login:'.hash('sha256', mb_strtolower($this->string('identificador')->trim()->toString()).'|'.$this->ip());
    }
}
