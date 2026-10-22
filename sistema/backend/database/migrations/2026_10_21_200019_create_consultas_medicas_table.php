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
        Schema::create('consultas_medicas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('historia_id')->constrained('historias_clinicas')->restrictOnDelete();
            $table->foreignId('cita_id')->unique()->constrained('citas_medicas')->restrictOnDelete();
            $table->foreignId('medico_id')->constrained('medicos')->restrictOnDelete();
            $table->timestamp('fecha_atencion');
            $table->decimal('peso_kg', 5, 2)->nullable();
            $table->decimal('talla_cm', 5, 2)->nullable();
            $table->integer('presion_sistolica')->nullable();
            $table->integer('presion_diastolica')->nullable();
            $table->decimal('temperatura_c', 4, 1)->nullable();
            $table->integer('escala_dolor')->nullable()->comment('Escala de dolor 0-10');
            $table->text('motivo_consulta')->nullable();
            $table->text('sintomas')->nullable();
            $table->text('evaluacion')->nullable();
            $table->text('observaciones')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultas_medicas');
    }
};
