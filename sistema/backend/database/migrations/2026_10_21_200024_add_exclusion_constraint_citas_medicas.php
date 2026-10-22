<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds the btree_gist extension and an exclusion constraint on citas_medicas
     * to prevent overlapping appointments for the same doctor (except cancelled/no-show).
     */
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist WITH SCHEMA public;');

        DB::statement("
            ALTER TABLE citas_medicas ADD CONSTRAINT citas_medicas_sin_solapamiento
            EXCLUDE USING gist (
                medico_id WITH =,
                tsrange(inicio, fin) WITH &&
            ) WHERE (estado NOT IN ('cancelada', 'inasistencia'));
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE citas_medicas DROP CONSTRAINT IF EXISTS citas_medicas_sin_solapamiento;');
    }
};
