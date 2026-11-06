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
        Schema::table('especialidades', function (Blueprint $table): void {
            $table->integer('tarifa_centavos')->nullable();
        });
        DB::statement('ALTER TABLE especialidades ADD CONSTRAINT especialidades_tarifa_valida CHECK (tarifa_centavos BETWEEN 1 AND 99999999)');
        Schema::create('cambios_tarifa_especialidad', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('especialidad_id')->constrained('especialidades')->restrictOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $table->integer('anterior_centavos')->nullable();
            $table->integer('nueva_centavos')->nullable();
            $table->timestampTz('fecha')->useCurrent();
            $table->index(['especialidad_id', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cambios_tarifa_especialidad');
        DB::statement('ALTER TABLE especialidades DROP CONSTRAINT especialidades_tarifa_valida');
        Schema::table('especialidades', fn (Blueprint $table) => $table->dropColumn('tarifa_centavos'));
    }
};
