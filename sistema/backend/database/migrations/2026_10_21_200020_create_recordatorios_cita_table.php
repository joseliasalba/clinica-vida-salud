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
        Schema::create('recordatorios_cita', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cita_id')->constrained('citas_medicas')->restrictOnDelete();
            $table->string('canal')->comment('Canal de envío: email, sms, whatsapp, push');
            $table->timestamp('fecha_programada');
            $table->string('estado')->comment('Estado: pendiente, enviado, fallido');
            $table->timestamp('fecha_envio')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recordatorios_cita');
    }
};
