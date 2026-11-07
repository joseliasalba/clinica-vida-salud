<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reembolsos_consulta', function (Blueprint $table): void {
            $table->id();
            $table->uuid('codigo')->unique();
            $table->foreignId('pago_id')->constrained('pagos_consulta')->restrictOnDelete();
            $table->foreignId('solicitado_por')->constrained('usuarios')->restrictOnDelete();
            $table->foreignId('revisado_por')->nullable()->constrained('usuarios')->restrictOnDelete();
            $table->integer('importe_centavos');
            $table->string('motivo', 500);
            $table->string('estado', 24)->default('solicitado');
            $table->string('decision_motivo', 500)->nullable();
            $table->string('referencia_devolucion', 100)->nullable();
            $table->string('stripe_refund_id')->nullable()->unique();
            $table->timestampTz('iniciado_en')->nullable();
            $table->timestampTz('completado_en')->nullable();
            $table->timestampsTz();
        });
        DB::statement("ALTER TABLE reembolsos_consulta ADD CONSTRAINT reembolso_estado_valido CHECK (estado IN ('solicitado','aprobado','procesando','completado','rechazado','fallido','revision'))");
        DB::statement('ALTER TABLE reembolsos_consulta ADD CONSTRAINT reembolso_importe_valido CHECK (importe_centavos > 0)');
        DB::statement("CREATE UNIQUE INDEX reembolso_activo_por_pago ON reembolsos_consulta (pago_id) WHERE estado <> 'rechazado'");
        DB::statement('CREATE UNIQUE INDEX reembolso_referencia_unica ON reembolsos_consulta (lower(btrim(referencia_devolucion))) WHERE referencia_devolucion IS NOT NULL');
        Schema::create('eventos_reembolso', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reembolso_id')->constrained('reembolsos_consulta')->restrictOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->restrictOnDelete();
            $table->string('stripe_event_id')->nullable()->unique();
            $table->string('accion', 32);
            $table->string('detalle', 500)->nullable();
            $table->timestampTz('fecha')->useCurrent();
        });
    }

    public function down(): void
    {
        if (DB::table('reembolsos_consulta')->exists()) {
            throw new RuntimeException('No se puede eliminar el historial de reembolsos.');
        }
        Schema::dropIfExists('eventos_reembolso');
        Schema::dropIfExists('reembolsos_consulta');
    }
};
