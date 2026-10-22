<?php

namespace App\Http\Requests\Auth;

use App\Rules\PasswordPolicy;
use App\Rules\UniqueUserIdentity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('usuarios.gestionar') ?? false;
    }

    protected function prepareForValidation(): void
    {
        foreach (['nombre_usuario', 'correo'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => mb_strtolower(trim($this->input($field)))]);
            }
        }
    }

    public function rules(): array
    {
        $id = $this->route('usuario')?->id;

        return [
            'nombre_usuario' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9][a-z0-9_.-]*$/', new UniqueUserIdentity('nombre_usuario', $id)],
            'correo' => ['required', 'email', 'max:255', new UniqueUserIdentity('correo', $id)],
            'password' => [$id ? 'nullable' : 'required', 'string', 'confirmed', new PasswordPolicy],
            'activo' => ['required', 'boolean'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'integer', 'distinct', Rule::exists('roles', 'id')->where('activo', true)],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Completa este campo.', 'string' => 'Ingresa un texto válido.',
            'correo.email' => 'Ingresa un correo válido.', 'max' => 'El valor ingresado es demasiado largo.',
            'nombre_usuario.regex' => 'Usa letras sin acentos, números, punto, guion o guion bajo; no uses @ ni espacios.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'roles.required' => 'Selecciona al menos un rol activo.', 'roles.min' => 'Selecciona al menos un rol activo.',
            'roles.*.exists' => 'Uno de los roles seleccionados no existe o está desactivado.',
            'roles.*.distinct' => 'No repitas roles.', 'roles.*.integer' => 'La selección de roles no es válida.',
            'roles.array' => 'La selección de roles no es válida.', 'activo.boolean' => 'El estado no es válido.',
        ];
    }
}
