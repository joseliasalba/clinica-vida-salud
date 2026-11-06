<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos_consulta', function (Blueprint $table): void {
            $table->id();
            $table->uuid('codigo')->unique();
            $table->foreignId('cita_id')->unique()->constrained('citas_medicas')->restrictOnDelete();
            $table->integer('importe_centavos');
            $table->string('moneda', 3)->default('bob');
            $table->string('estado', 20)->default('nuevo');
            $table->string('stripe_session_id')->nullable()->unique();
            $table->string('stripe_payment_intent')->nullable()->unique();
            $table->string('stripe_invoice_id')->nullable()->unique();
            $table->text('checkout_url')->nullable();
            $table->jsonb('checkout_payload')->nullable();
            $table->timestampTz('vence_en');
            $table->timestampTz('pagado_en')->nullable();
            $table->timestampsTz();
            $table->index(['estado', 'vence_en']);
        });
        DB::statement("ALTER TABLE pagos_consulta ADD CONSTRAINT pago_importe_valido CHECK (importe_centavos BETWEEN 1 AND 99999999 AND moneda = 'bob')");
        DB::statement("ALTER TABLE pagos_consulta ADD CONSTRAINT pago_estado_valido CHECK (estado IN ('nuevo','incierto','pendiente','procesando','pagado','vencido','revision'))");
        Schema::create('eventos_pago', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pago_id')->constrained('pagos_consulta')->restrictOnDelete();
            $table->string('stripe_event_id')->nullable()->unique();
            $table->string('anterior', 20);
            $table->string('nuevo', 20);
            $table->timestampTz('fecha')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos_pago');
        Schema::dropIfExists('pagos_consulta');
    }
};
