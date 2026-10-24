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
        if (DB::scalar("SELECT count(*) FROM (SELECT nullif(upper(btrim(registro_profesional)), '') FROM medicos WHERE nullif(btrim(registro_profesional), '') IS NOT NULL GROUP BY 1 HAVING count(*) > 1) duplicados") > 0) {
            throw new RuntimeException('Existen registros profesionales duplicados al normalizarlos. Revise los médicos antes de aplicar la migración.');
        }
        DB::statement("CREATE UNIQUE INDEX medicos_registro_normalizado_unique ON medicos ((nullif(upper(btrim(registro_profesional)), '')))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX medicos_registro_normalizado_unique');
    }
};
