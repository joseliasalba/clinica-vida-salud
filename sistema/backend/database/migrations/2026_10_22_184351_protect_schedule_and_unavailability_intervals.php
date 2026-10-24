<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE horarios_atencion ADD CONSTRAINT horarios_datos_validos CHECK (
            dia_semana BETWEEN 1 AND 7 AND hora_fin > hora_inicio AND duracion_turno_min BETWEEN 1 AND 480
            AND extract(epoch FROM (hora_fin - hora_inicio)) >= duracion_turno_min * 60
            AND mod(extract(epoch FROM (hora_fin - hora_inicio))::integer, nullif(duracion_turno_min * 60, 0)) = 0
            AND (vigente_hasta IS NULL OR vigente_hasta >= vigente_desde)
            AND (vigente_hasta IS NULL OR vigente_hasta - vigente_desde >= mod(dia_semana - extract(isodow FROM vigente_desde)::integer + 7, 7)))');
        // Cada semana que contiene el día elegido es una unidad del rango; evita falsos conflictos entre vigencias sin una fecha de atención común.
        DB::statement("ALTER TABLE horarios_atencion ADD CONSTRAINT horarios_sin_solapamiento EXCLUDE USING gist (
            medico_id WITH =, dia_semana WITH =,
            int4range(extract(epoch FROM hora_inicio)::integer, extract(epoch FROM hora_fin)::integer, '[)') WITH &&,
            int8range(ceil(((vigente_desde - DATE '2000-01-03') - (dia_semana - 1)) / 7.0)::bigint,
                CASE WHEN vigente_hasta IS NULL THEN NULL ELSE floor(((vigente_hasta - DATE '2000-01-03') - (dia_semana - 1)) / 7.0)::bigint + 1 END, '[)') WITH &&
        ) WHERE (activo)");
        DB::statement('ALTER TABLE indisponibilidades_medico ADD CONSTRAINT indisponibilidades_intervalo_valido CHECK (fin > inicio)');
        DB::statement("ALTER TABLE indisponibilidades_medico ADD CONSTRAINT indisponibilidades_sin_solapamiento EXCLUDE USING gist (medico_id WITH =, tsrange(inicio, fin, '[)') WITH &&) WHERE (activo)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE indisponibilidades_medico DROP CONSTRAINT indisponibilidades_sin_solapamiento, DROP CONSTRAINT indisponibilidades_intervalo_valido');
        DB::statement('ALTER TABLE horarios_atencion DROP CONSTRAINT horarios_sin_solapamiento, DROP CONSTRAINT horarios_datos_validos');
    }
};
