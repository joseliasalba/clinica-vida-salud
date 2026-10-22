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
        Schema::create('solicitudes_estudio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consulta_id')->constrained('consultas_medicas')->restrictOnDelete();
            $table->string('nombre_estudio');
            $table->text('indicaciones')->nullable();
            $table->string('estado')->comment('Estado: solicitado, en_proceso, completado, cancelado');
            $table->timestamp('fecha_solicitud')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes_estudio');
    }
};
