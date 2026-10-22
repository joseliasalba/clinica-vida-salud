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
        Schema::create('cambios_cita', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cita_id')->constrained('citas_medicas')->restrictOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $table->string('accion')->comment('Acción realizada: creacion, modificacion, cancelacion, confirmacion');
            $table->json('datos_anteriores')->nullable();
            $table->json('datos_nuevos');
            $table->string('motivo')->nullable();
            $table->timestamp('fecha_cambio')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cambios_cita');
    }
};
