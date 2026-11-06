<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resultados_estudio', function (Blueprint $table): void {
            $table->unique('solicitud_id');
        });
        DB::statement("ALTER TABLE solicitudes_estudio ADD CONSTRAINT estudios_estado_valido CHECK (estado IN ('solicitado', 'en_proceso', 'completado', 'cancelado'))");
        DB::statement("ALTER TABLE resultados_estudio ADD CONSTRAINT resultados_publicacion_valida CHECK (estado_publicacion IN ('borrador', 'publicado', 'retirado'))");
        DB::statement("ALTER TABLE resultados_estudio ADD CONSTRAINT resultados_publicados_completos CHECK (estado_publicacion <> 'publicado' OR (publicado_por IS NOT NULL AND fecha_publicacion IS NOT NULL AND (NULLIF(TRIM(descripcion), '') IS NOT NULL OR archivo_privado IS NOT NULL)))");
        Schema::create('eventos_estudio', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('solicitud_id')->constrained('solicitudes_estudio')->restrictOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $table->string('accion', 40);
            $table->string('motivo', 500)->nullable();
            $table->timestamp('fecha')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos_estudio');
        DB::statement('ALTER TABLE resultados_estudio DROP CONSTRAINT resultados_publicados_completos');
        DB::statement('ALTER TABLE resultados_estudio DROP CONSTRAINT resultados_publicacion_valida');
        DB::statement('ALTER TABLE solicitudes_estudio DROP CONSTRAINT estudios_estado_valido');
        Schema::table('resultados_estudio', fn (Blueprint $table) => $table->dropUnique(['solicitud_id']));
    }
};
