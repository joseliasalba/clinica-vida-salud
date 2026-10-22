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
        Schema::create('horarios_atencion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medico_id')->constrained('medicos')->restrictOnDelete();
            $table->integer('dia_semana')->comment('1=Lunes ... 7=Domingo');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->integer('duracion_turno_min')->comment('Duración de cada turno en minutos');
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horarios_atencion');
    }
};
