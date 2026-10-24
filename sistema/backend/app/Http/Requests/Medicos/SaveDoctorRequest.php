<?php

namespace App\Http\Requests\Medicos;

use App\Models\Medico;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SaveDoctorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('medicos.gestionar') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombres' => ['required', 'string', 'max:150'],
            'apellidos' => ['required', 'string', 'max:150'],
            'registro_profesional' => ['bail', 'required', 'string', 'max:100', 'regex:/^[\pL\pN][\pL\pN .\/-]*$/u', function (string $attribute, mixed $value, Closure $fail): void {
                if (Medico::query()->whereRaw('upper(btrim(registro_profesional)) = ?', [$value])->when($this->route('medico'), fn ($query, Medico $medico) => $query->where('id', '<>', $medico->id))->exists()) {
                    $fail('Ese registro profesional ya existe, incluso si el médico está inactivo.');
                }
            }],
            'telefono' => ['required', 'string', 'max:50', 'regex:/^\+?[0-9][0-9 ()\-.]{4,49}$/'],
            'correo' => ['required', 'email', 'max:255'],
            'usuario_id' => ['nullable', 'integer', Rule::exists('usuarios', 'id')],
            'especialidades' => ['required', 'array', 'min:1', 'max:100'],
            'especialidades.*' => ['bail', 'required', 'integer', 'distinct', Rule::exists('especialidades', 'id')->where('activo', true)],
            'version' => [$this->route('medico') ? 'required' : 'nullable', 'string', 'size:64'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['nombres', 'apellidos', 'registro_profesional', 'telefono', 'correo'] as $field) {
            if (is_string($this->input($field))) {
                $value = trim($this->input($field));
                $value = match ($field) {
                    'registro_profesional' => mb_strtoupper($value),
                    'correo' => mb_strtolower($value),
                    'nombres', 'apellidos' => Str::squish($value),
                    default => $value,
                };
                $this->merge([$field => $value]);
            }
        }
    }

    public function messages(): array
    {
        return [
            'required' => 'Completa este campo.', 'string' => 'Ingresa un texto válido.',
            'max' => 'El valor ingresado es demasiado largo.', 'correo.email' => 'Ingresa un correo válido.',
            'registro_profesional.regex' => 'Ingresa un registro profesional válido.', 'telefono.regex' => 'Ingresa un teléfono válido.',
            'especialidades.required' => 'Selecciona al menos una especialidad activa.', 'especialidades.array' => 'La selección de especialidades no es válida.',
            'especialidades.*.exists' => 'Una especialidad no existe o está desactivada.', 'especialidades.*.distinct' => 'No repitas especialidades.', 'especialidades.*.integer' => 'La selección de especialidades no es válida.',
            'usuario_id.exists' => 'La cuenta seleccionada no existe.', 'usuario_id.integer' => 'La cuenta no es válida.',
            'version.required' => 'Recarga la ficha antes de guardar.', 'version.size' => 'Recarga la ficha antes de guardar.',
        ];
    }
}
