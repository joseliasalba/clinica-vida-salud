<?php

namespace App\Http\Requests\Pacientes;

use App\Models\Paciente;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class SavePatientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('pacientes.gestionar') ?? false;
    }

    protected function prepareForValidation(): void
    {
        foreach (['ci', 'nombres', 'apellidos', 'sexo', 'telefono', 'correo', 'direccion'] as $field) {
            if (is_string($this->input($field))) {
                $value = trim($this->input($field));
                $value = match ($field) {
                    'ci' => mb_strtoupper($value),
                    'correo' => mb_strtolower($value),
                    default => $value,
                };
                $this->merge([$field => $value === '' ? null : $value]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'ci' => ['bail', 'nullable', 'string', 'max:50', 'regex:/^[\pL\pN][\pL\pN .\/-]*$/u', function (string $attribute, mixed $value, Closure $fail): void {
                $query = Paciente::query()->whereRaw("nullif(upper(btrim(ci)), '') = ?", [$value]);
                if ($paciente = $this->route('paciente')) {
                    $query->where('id', '<>', $paciente->id);
                }
                if ($query->exists()) {
                    $fail('Ya existe un paciente con ese CI. Busca su ficha antes de registrar otro.');
                }
            }],
            'nombres' => ['required', 'string', 'max:150'],
            'apellidos' => ['required', 'string', 'max:150'],
            'fecha_nacimiento' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'sexo' => ['nullable', 'string', 'max:30'],
            'telefono' => ['required', 'string', 'max:50', 'regex:/^\+?[0-9][0-9 ()\-.]{4,49}$/'],
            'correo' => ['nullable', 'email', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'activo' => ['required', 'boolean'],
            'version' => [$this->route('paciente') ? 'required' : 'nullable', 'string', 'size:64'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Completa este campo.', 'string' => 'Ingresa un texto válido.',
            'max' => 'El valor ingresado es demasiado largo.', 'ci.regex' => 'Ingresa un CI válido, con su complemento si corresponde.',
            'fecha_nacimiento.date_format' => 'Ingresa una fecha válida.',
            'fecha_nacimiento.before_or_equal' => 'La fecha de nacimiento no puede ser futura.',
            'telefono.regex' => 'Ingresa un teléfono válido; puedes usar +, espacios, guiones y paréntesis.',
            'correo.email' => 'Ingresa un correo válido.', 'activo.boolean' => 'El estado no es válido.',
            'version.required' => 'Recarga la ficha antes de guardar los cambios.',
            'version.size' => 'Recarga la ficha antes de guardar los cambios.',
        ];
    }
}
