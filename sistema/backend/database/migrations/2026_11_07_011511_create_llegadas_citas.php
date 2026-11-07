<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('llegadas_citas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cita_id')->unique()->constrained('citas_medicas')->restrictOnDelete();
            $table->foreignId('registrada_por')->constrained('usuarios')->restrictOnDelete();
            $table->foreignId('medico_id')->constrained('medicos')->restrictOnDelete();
            $table->timestamp('inicio_programado');
            $table->timestampTz('llegada_en');
            $table->index(['llegada_en', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('llegadas_citas')->exists()) {
            throw new RuntimeException('No se puede eliminar el historial de llegadas.');
        }
        Schema::dropIfExists('llegadas_citas');
    }
};
