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
        Schema::create('paciente_enfermedad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignId('enfermedad_id')->constrained('enfermedades')->restrictOnDelete();
            $table->date('fecha_diagnostico')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('origen')->comment('Origen del diagnóstico: consulta, declarado, externo');
            $table->foreignId('registrado_por')->constrained('usuarios')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paciente_enfermedad');
    }
};
