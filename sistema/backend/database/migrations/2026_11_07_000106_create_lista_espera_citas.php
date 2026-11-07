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
        Schema::create('lista_espera_citas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignId('especialidad_id')->constrained('especialidades')->restrictOnDelete();
            $table->foreignId('medico_id')->nullable()->constrained('medicos')->restrictOnDelete();
            $table->date('fecha');
            $table->string('estado', 20)->default('esperando');
            $table->foreignId('creado_por')->constrained('usuarios')->restrictOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('usuarios')->restrictOnDelete();
            $table->timestampsTz();
            $table->index(['fecha', 'especialidad_id', 'estado', 'id']);
        });
        DB::statement("ALTER TABLE lista_espera_citas ADD CONSTRAINT lista_espera_estado_check CHECK (estado IN ('esperando','contactado','finalizado','cancelado'))");
        DB::statement("CREATE UNIQUE INDEX lista_espera_activa_unica ON lista_espera_citas (paciente_id, especialidad_id, fecha) WHERE estado IN ('esperando','contactado')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('lista_espera_citas')->exists()) {
            throw new RuntimeException('No se puede eliminar el historial de la lista de espera.');
        }
        Schema::dropIfExists('lista_espera_citas');
    }
};
