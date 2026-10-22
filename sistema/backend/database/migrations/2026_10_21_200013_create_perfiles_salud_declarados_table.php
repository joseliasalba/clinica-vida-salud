<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('perfiles_salud_declarados', function (Blueprint $table) {
            $table->foreignId('paciente_id')->primary()->constrained('pacientes')->restrictOnDelete();
            $table->string('tipo_sangre')->nullable();
            $table->string('estado_alergias');
            $table->string('detalle_alergias')->nullable();
            $table->text('medicamentos')->nullable();
            $table->text('antecedentes_personales')->nullable();
            $table->text('antecedentes_familiares')->nullable();
            $table->timestamp('fecha_actualizacion')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perfiles_salud_declarados');
    }
};
