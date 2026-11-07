<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('preferencias_paciente', function (Blueprint $table): void {
            $table->boolean('avisos_resultados_activos')->default(false);
        });
        Schema::create('avisos_resultados', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('evento_estudio_id')->unique()->constrained('eventos_estudio')->restrictOnDelete();
            $table->foreignId('solicitud_id')->constrained('solicitudes_estudio')->restrictOnDelete();
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $table->string('estado', 16)->default('pendiente');
            $table->timestampTz('fecha_intento')->nullable();
            $table->timestampTz('fecha_envio')->nullable();
            $table->timestampTz('creado_en')->useCurrent();
            $table->index(['estado', 'id']);
        });
        DB::statement("ALTER TABLE avisos_resultados ADD CONSTRAINT aviso_resultado_estado CHECK (estado IN ('pendiente','enviando','enviado','fallido','omitido','simulado'))");
    }

    public function down(): void
    {
        if (DB::table('avisos_resultados')->exists()) {
            throw new RuntimeException('No se puede eliminar el historial de avisos.');
        }
        Schema::dropIfExists('avisos_resultados');
        Schema::table('preferencias_paciente', fn (Blueprint $table) => $table->dropColumn('avisos_resultados_activos'));
    }
};
