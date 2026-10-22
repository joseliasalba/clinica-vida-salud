<?php

namespace App\Http\Requests\Pacientes;

use App\Models\Paciente;
use App\Rules\PasswordPolicy;
use App\Rules\UniqueUserIdentity;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RegisterPatientRequest extends SavePatientRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() === null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['activo'], $rules['version']);

        return array_replace($rules, [
            'ci' => ['bail', 'nullable', 'string', 'max:50', 'regex:/^[\pL\pN][\pL\pN .\/-]*$/u', function (string $attribute, mixed $value, Closure $fail): void {
                if (Paciente::query()->whereRaw("nullif(upper(btrim(ci)), '') = ?", [$value])->exists()) {
                    $fail('No podemos registrar otra ficha con esos datos. Si ya fuiste atendido, contacta a recepción para revisar tu acceso.');
                }
            }],
            'nombre_usuario' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9][a-z0-9_.-]*$/', new UniqueUserIdentity('nombre_usuario')],
            'correo' => ['required', 'email', 'max:255', new UniqueUserIdentity('correo')],
            'password' => ['required', 'string', 'confirmed', new PasswordPolicy],
        ]);
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        if (is_string($this->input('nombre_usuario'))) {
            $this->merge(['nombre_usuario' => mb_strtolower(trim($this->input('nombre_usuario')))]);
        }
    }

    public function messages(): array
    {
        return parent::messages() + [
            'nombre_usuario.regex' => 'Usa letras sin acentos, números, punto, guion o guion bajo; sin espacios ni @.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ];
    }
}
