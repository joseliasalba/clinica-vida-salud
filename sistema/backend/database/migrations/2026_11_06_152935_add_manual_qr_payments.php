<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('codigos_qr_pago', function (Blueprint $table): void {
            $table->id();
            $table->text('imagen_base64');
            $table->string('mime', 32);
            $table->boolean('activo')->default(true);
            $table->foreignId('creado_por')->constrained('usuarios')->restrictOnDelete();
            $table->timestampsTz();
        });
        DB::statement('CREATE UNIQUE INDEX un_solo_qr_activo ON codigos_qr_pago (activo) WHERE activo = true');
        Schema::table('pagos_consulta', function (Blueprint $table): void {
            $table->string('metodo', 16)->nullable();
            $table->foreignId('qr_id')->nullable()->constrained('codigos_qr_pago')->restrictOnDelete();
            $table->string('referencia_qr', 100)->nullable();
            $table->timestampTz('reportado_en')->nullable();
        });
        DB::statement("UPDATE pagos_consulta SET metodo = 'stripe' WHERE stripe_session_id IS NOT NULL OR checkout_payload IS NOT NULL");
        DB::statement("ALTER TABLE pagos_consulta ADD CONSTRAINT pago_metodo_valido CHECK (metodo IS NULL OR metodo IN ('stripe', 'qr'))");
        DB::statement('ALTER TABLE pagos_consulta DROP CONSTRAINT pago_estado_valido');
        DB::statement("ALTER TABLE pagos_consulta ADD CONSTRAINT pago_estado_valido CHECK (estado IN ('nuevo','incierto','pendiente','procesando','pagado','vencido','revision','qr_pendiente','qr_revision','qr_rechazado'))");
        DB::statement('CREATE UNIQUE INDEX pagos_referencia_qr_unica ON pagos_consulta (lower(btrim(referencia_qr))) WHERE referencia_qr IS NOT NULL');
        Schema::create('eventos_pago_manual', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pago_id')->constrained('pagos_consulta')->restrictOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $table->string('accion', 32);
            $table->string('motivo', 500)->nullable();
            $table->timestampTz('fecha')->useCurrent();
        });
    }

    public function down(): void
    {
        if (DB::table('pagos_consulta')->where('metodo', 'qr')->exists()) {
            throw new RuntimeException('No se puede revertir mientras existan pagos QR.');
        }
        Schema::dropIfExists('eventos_pago_manual');
        DB::statement('DROP INDEX pagos_referencia_qr_unica');
        DB::statement('ALTER TABLE pagos_consulta DROP CONSTRAINT pago_estado_valido');
        DB::statement("ALTER TABLE pagos_consulta ADD CONSTRAINT pago_estado_valido CHECK (estado IN ('nuevo','incierto','pendiente','procesando','pagado','vencido','revision'))");
        DB::statement('ALTER TABLE pagos_consulta DROP CONSTRAINT pago_metodo_valido');
        Schema::table('pagos_consulta', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('qr_id');
            $table->dropColumn(['metodo', 'referencia_qr', 'reportado_en']);
        });
        Schema::dropIfExists('codigos_qr_pago');
    }
};
