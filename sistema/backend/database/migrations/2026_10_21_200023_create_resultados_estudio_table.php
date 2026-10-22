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
        Schema::create('resultados_estudio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->constrained('solicitudes_estudio')->restrictOnDelete();
            $table->text('descripcion')->nullable();
            $table->string('archivo_privado')->nullable();
            $table->string('estado_publicacion')->comment('Estado: borrador, publicado, retirado');
            $table->foreignId('publicado_por')->nullable()->constrained('usuarios')->restrictOnDelete();
            $table->timestamp('fecha_publicacion')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resultados_estudio');
    }
};
