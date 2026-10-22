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
        Schema::create('citas_medicas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignId('medico_id')->constrained('medicos')->restrictOnDelete();
            $table->foreignId('especialidad_id')->constrained('especialidades')->restrictOnDelete();
            $table->foreignId('horario_id')->constrained('horarios_atencion')->restrictOnDelete();
            $table->timestamp('inicio');
            $table->timestamp('fin');
            $table->string('estado')->comment('programada, confirmada, en_curso, completada, cancelada, inasistencia');
            $table->string('motivo')->nullable();
            $table->string('motivo_cancelacion')->nullable();
            $table->foreignId('creada_por')->constrained('usuarios')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('citas_medicas');
    }
};
