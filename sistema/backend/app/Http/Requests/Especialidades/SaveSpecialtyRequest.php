<?php

namespace App\Http\Requests\Especialidades;

use App\Models\Especialidad;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class SaveSpecialtyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('medicos.gestionar') ?? false;
    }

    protected function prepareForValidation(): void
    {
        foreach (['nombre', 'descripcion', 'tarifa_consulta'] as $field) {
            if (is_string($this->input($field))) {
                $value = $field === 'nombre' ? Str::squish($this->input($field)) : trim($this->input($field));
                $this->merge([$field => $value === '' ? null : $value]);
            }
        }
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'nombre' => ['bail', 'required', 'string', 'max:255', function (string $attribute, mixed $value, Closure $fail): void {
                $normalized = Especialidad::normalizeName($value);
                if (mb_strlen($normalized) > 255 || $normalized === '') {
                    $fail('Ingresa un nombre válido de hasta 255 caracteres.');

                    return;
                }
                $query = Especialidad::query()->where('nombre_normalizado', $normalized);
                if ($especialidad = $this->route('especialidad')) {
                    $query->where('id', '<>', $especialidad->id);
                }
                if ($query->exists()) {
                    $fail('Ya existe una especialidad con ese nombre. Revisa también las especialidades inactivas.');
                }
            }],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'tarifa_consulta' => ['bail', 'nullable', 'string', 'regex:/\A(?:0|[1-9][0-9]{0,5})(?:\.[0-9]{1,2})?\z/', 'numeric', 'gt:0'],
            'version' => [$this->route('especialidad') ? 'required' : 'nullable', 'string', 'size:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nombre.required' => 'Ingresa el nombre de la especialidad.',
            'tarifa_consulta.regex' => 'Ingresa un importe de 0.01 a 999999.99 Bs, con punto decimal y sin separadores de miles.',
            'tarifa_consulta.gt' => 'La tarifa debe ser mayor que cero; deja el campo vacío si todavía no está definida.',
            'string' => 'Ingresa un texto válido.',
            'max' => 'El valor ingresado no puede superar los 255 caracteres.',
            'version.required' => 'Recarga la especialidad antes de guardar los cambios.',
            'version.size' => 'Recarga la especialidad antes de guardar los cambios.',
        ];
    }
}
