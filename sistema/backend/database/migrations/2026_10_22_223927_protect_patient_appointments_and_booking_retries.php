<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citas_medicas', function (Blueprint $table): void {
            $table->uuid('reserva_token')->nullable()->unique();
        });
        DB::statement('ALTER TABLE citas_medicas ADD CONSTRAINT citas_intervalo_valido CHECK (fin > inicio)');
        DB::statement("ALTER TABLE citas_medicas ADD CONSTRAINT citas_paciente_sin_solapamiento EXCLUDE USING gist (paciente_id WITH =, tsrange(inicio, fin, '[)') WITH &&) WHERE (estado NOT IN ('cancelada', 'inasistencia'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE citas_medicas DROP CONSTRAINT citas_paciente_sin_solapamiento');
        DB::statement('ALTER TABLE citas_medicas DROP CONSTRAINT citas_intervalo_valido');
        Schema::table('citas_medicas', fn (Blueprint $table) => $table->dropColumn('reserva_token'));
    }
};
