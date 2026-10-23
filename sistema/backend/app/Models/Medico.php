<?php

namespace App\Models;

use Database\Factories\MedicoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medico extends Model
{
    /** @use HasFactory<MedicoFactory> */
    use HasFactory;

    protected $table = 'medicos';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
