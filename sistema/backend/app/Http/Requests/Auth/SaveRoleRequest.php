<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('usuarios.gestionar') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('nombre'))) {
            $this->merge(['nombre' => mb_strtolower(trim($this->input('nombre')))]);
        }
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:50', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique('roles', 'nombre')->ignore($this->route('rol'))],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'activo' => ['required', 'boolean'],
            'permisos' => ['required', 'array', 'min:1'],
            'permisos.*' => ['required', 'integer', 'distinct', Rule::exists('permisos', 'id')->where('activo', true)],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Completa este campo.', 'max' => 'El valor ingresado es demasiado largo.',
            'nombre.unique' => 'Ese nombre de rol ya está registrado.',
            'nombre.regex' => 'Usa letras sin acentos, números o guion bajo; empieza con una letra.',
            'permisos.required' => 'Selecciona al menos un permiso activo.', 'permisos.min' => 'Selecciona al menos un permiso activo.',
            'permisos.*.exists' => 'Uno de los permisos no existe o está desactivado.',
            'permisos.*.distinct' => 'No repitas permisos.', 'permisos.*.integer' => 'La selección de permisos no es válida.',
            'permisos.array' => 'La selección de permisos no es válida.', 'activo.boolean' => 'El estado no es válido.',
        ];
    }
}
