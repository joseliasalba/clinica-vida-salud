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
        if (DB::scalar("SELECT count(*) FROM (SELECT nullif(upper(btrim(ci)), '') FROM pacientes WHERE nullif(btrim(ci), '') IS NOT NULL GROUP BY 1 HAVING count(*) > 1) duplicados") > 0) {
            throw new RuntimeException('Existen CI duplicados al normalizarlos. Revise las fichas antes de aplicar esta migración.');
        }
        DB::statement("CREATE UNIQUE INDEX pacientes_ci_normalizado_unique ON pacientes ((nullif(upper(btrim(ci)), '')))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX pacientes_ci_normalizado_unique');
    }
};
