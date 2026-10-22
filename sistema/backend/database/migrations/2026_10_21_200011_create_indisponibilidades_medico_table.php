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
        Schema::create('indisponibilidades_medico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medico_id')->constrained('medicos')->restrictOnDelete();
            $table->timestamp('inicio');
            $table->timestamp('fin');
            $table->string('motivo');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indisponibilidades_medico');
    }
};
