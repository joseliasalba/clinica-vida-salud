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
            $table->unsignedSmallInteger('anticipacion_horas')->default(24);
        });
        Schema::table('recordatorios_cita', function (Blueprint $table): void {
            $table->timestamp('inicio_cita')->nullable();
            $table->timestamp('fecha_intento')->nullable();
            $table->unique(['cita_id', 'inicio_cita', 'canal'], 'recordatorios_ocurrencia_unica');
            $table->index(['estado', 'fecha_programada']);
        });
        DB::statement('UPDATE recordatorios_cita r SET inicio_cita = c.inicio FROM citas_medicas c WHERE c.id = r.cita_id');
        DB::statement('ALTER TABLE recordatorios_cita ALTER COLUMN inicio_cita SET NOT NULL');
        DB::statement('ALTER TABLE preferencias_paciente ADD CONSTRAINT recordatorios_anticipacion_valida CHECK (anticipacion_horas IN (2, 24, 48))');
        DB::statement("ALTER TABLE recordatorios_cita ADD CONSTRAINT recordatorios_estado_valido CHECK (estado IN ('pendiente', 'enviando', 'enviado', 'fallido', 'omitido', 'simulado'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE preferencias_paciente DROP CONSTRAINT recordatorios_anticipacion_valida');
        DB::statement('ALTER TABLE recordatorios_cita DROP CONSTRAINT recordatorios_estado_valido');
        Schema::table('recordatorios_cita', function (Blueprint $table): void {
            $table->dropUnique('recordatorios_ocurrencia_unica');
            $table->dropIndex(['estado', 'fecha_programada']);
            $table->dropColumn(['inicio_cita', 'fecha_intento']);
        });
        Schema::table('preferencias_paciente', fn (Blueprint $table) => $table->dropColumn('anticipacion_horas'));
    }
};
