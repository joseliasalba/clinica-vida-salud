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
        Schema::create('preferencias_paciente', function (Blueprint $table) {
            $table->foreignId('paciente_id')->primary()->constrained('pacientes')->restrictOnDelete();
            $table->boolean('recordatorios_activos')->default(true);
            $table->string('canal_preferido')->nullable();
            $table->json('preferencias_privacidad')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preferencias_paciente');
    }
};
