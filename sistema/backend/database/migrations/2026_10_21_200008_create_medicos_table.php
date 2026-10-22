<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('medicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->unique()->constrained('usuarios')->restrictOnDelete();
            $table->string('codigo')->unique();
            $table->string('registro_profesional')->unique();
            $table->string('nombres');
            $table->string('apellidos');
            $table->string('telefono');
            $table->string('correo');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicos');
    }
};
