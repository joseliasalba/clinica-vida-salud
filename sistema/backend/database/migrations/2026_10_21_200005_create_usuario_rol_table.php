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
        Schema::create('usuario_rol', function (Blueprint $table) {
            $table->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $table->foreignId('rol_id')->constrained('roles')->restrictOnDelete();
            $table->timestamp('fecha_asignacion')->useCurrent();
            $table->timestamp('fecha_expiracion')->nullable();
            $table->boolean('activo')->default(true);

            $table->primary(['usuario_id', 'rol_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuario_rol');
    }
};
