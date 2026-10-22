<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueUserIdentity implements ValidationRule
{
    public function __construct(private string $column, private ?int $ignoreId = null)
    {
        if (! in_array($column, ['nombre_usuario', 'correo'], true)) {
            throw new \InvalidArgumentException('Campo de identidad inválido.');
        }
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && User::query()->whereRaw('lower('.$this->column.') = ?', [mb_strtolower($value)])
            ->when($this->ignoreId, fn ($query) => $query->where('id', '!=', $this->ignoreId))->exists()) {
            $fail($this->column === 'correo' ? 'Ese correo ya está registrado.' : 'Ese nombre de usuario ya está registrado.');
        }
    }
}
