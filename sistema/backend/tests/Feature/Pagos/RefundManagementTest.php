<?php

namespace Tests\Feature\Pagos;

use App\Models\PagoConsulta;
use App\Models\ReembolsoConsulta;
use App\Services\Pagos\PaymentManagement;
use App\Services\Pagos\RefundManagement;
use App\Services\Pagos\StripeGateway;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Support\CreatesAdministrativeUsers;
use Tests\Support\CreatesAppointments;
use Tests\TestCase;

class RefundManagementTest extends TestCase
{
    use CreatesAdministrativeUsers, CreatesAppointments, DatabaseTransactions;

    private function paid(string $method = 'qr'): array
    {
        $patient = $this->accountWithRole('paciente');
        $patient->forceFill(['email_verified_at' => now()])->save();
        $appointment = $this->createAppointment(['estado' => 'cancelada', 'creada_por' => $patient->id]);
        $appointment->paciente->forceFill(['usuario_id' => $patient->id])->save();
        $payment = PagoConsulta::factory()->create(['cita_id' => $appointment->id, 'estado' => 'pagado', 'metodo' => $method,
            'pagado_en' => now(), 'referencia_qr' => $method === 'qr' ? 'PAGO-'.fake()->uuid() : null,
            'stripe_payment_intent' => $method === 'stripe' ? 'pi_test_'.fake()->uuid() : null]);

        return [$patient, $payment->fresh()];
    }

    public function test_reception_requests_and_only_admin_can_approve_and_record_qr_refund(): void
    {
        [$patient, $payment] = $this->paid();
        $reception = $this->accountWithRole('recepcion');
        $admin = $this->accountWithRole('administrador');
        $service = app(RefundManagement::class);
        $refund = $service->request($reception, $payment, 'Cita cancelada');
        $this->assertSame('solicitado', $refund->estado);
        $this->assertSame(15000, $refund->importe_centavos);
        try {
            $service->decide($reception, $refund, true, null);
            $this->fail('Recepción no debe aprobar.');
        } catch (HttpException $error) {
            $this->assertSame(403, $error->getStatusCode());
        }
        $service->decide($admin, $refund, true, null);
        $this->assertSame('aprobado', $refund->fresh()->estado);
        $this->assertNull($refund->fresh()->completado_en);
        $service->recordManual($admin, $refund, 'DEVOLUCION-123');
        $this->assertSame('completado', $refund->fresh()->estado);
        $this->assertDatabaseHas('eventos_reembolso', ['reembolso_id' => $refund->id, 'usuario_id' => $admin->id, 'accion' => 'registrado_manual']);
        $this->expectException(ValidationException::class);
        $service->recordManual($admin, $refund, 'OTRA');
    }

    public function test_patient_can_only_request_for_own_paid_cancelled_appointment(): void
    {
        [$patient, $payment] = $this->paid('stripe');
        [$other, $otherPayment] = $this->paid('stripe');
        $service = app(RefundManagement::class);
        try {
            $service->request($patient, $otherPayment, 'Solicitud ajena');
            $this->fail('El paciente no debe acceder a pagos ajenos.');
        } catch (HttpException $error) {
            $this->assertSame(403, $error->getStatusCode());
        }
        $payment->cita->forceFill(['estado' => 'confirmada'])->save();
        $this->expectException(ValidationException::class);
        $service->request($patient, $payment, 'Cita no cancelada');
    }

    public function test_rejected_request_can_be_replaced_but_active_or_completed_request_cannot(): void
    {
        [$patient, $payment] = $this->paid('stripe');
        $service = app(RefundManagement::class);
        $first = $service->request($patient, $payment, 'Primera');
        $service->decide($this->accountWithRole('administrador'), $first, false, 'No aplica');
        $this->assertSame('rechazado', $first->fresh()->estado);
        $service->request($patient, $payment, 'Segunda');
        $this->expectException(ValidationException::class);
        $service->request($patient, $payment, 'Duplicada');
    }

    public function test_stripe_retry_uses_same_request_and_only_verified_success_completes(): void
    {
        [$patient, $payment] = $this->paid('stripe');
        $admin = $this->accountWithRole('administrador');
        $service = app(RefundManagement::class);
        $refund = $service->request($patient, $payment, 'Cancelación');
        $service->decide($admin, $refund, true, null);
        $this->mock(StripeGateway::class, function ($mock) use ($payment, $refund): void {
            $mock->shouldReceive('createRefund')->once()->ordered()
                ->with($payment->stripe_payment_intent, 15000, $refund->codigo)
                ->andThrow(new RuntimeException('Tiempo agotado'));
            $mock->shouldReceive('createRefund')->once()->ordered()
                ->with($payment->stripe_payment_intent, 15000, $refund->codigo)
                ->andReturn(['id' => 're_test_refund', 'livemode' => false, 'payment_intent' => $payment->stripe_payment_intent,
                    'amount' => 15000, 'currency' => 'bob', 'metadata' => ['reembolso_codigo' => $refund->codigo], 'status' => 'succeeded']);
        });
        $service = app(RefundManagement::class);
        try {
            $service->processStripe($admin, $refund);
            $this->fail('Debe registrar estado incierto.');
        } catch (RuntimeException) {
            $this->assertSame('procesando', $refund->fresh()->estado);
        }
        $service->processStripe($admin, $refund->fresh());
        $this->assertSame('completado', $refund->fresh()->estado);
        $this->assertSame('re_test_refund', $refund->fresh()->stripe_refund_id);
    }

    public function test_mismatched_or_live_stripe_response_never_completes_refund(): void
    {
        [$patient, $payment] = $this->paid('stripe');
        $service = app(RefundManagement::class);
        $refund = $service->request($patient, $payment, 'Cancelación');
        $refund->forceFill(['estado' => 'procesando', 'iniciado_en' => now()])->save();
        $result = ['id' => 're_test', 'livemode' => false, 'payment_intent' => $payment->stripe_payment_intent,
            'amount' => 15000, 'currency' => 'bob', 'metadata' => ['reembolso_codigo' => $refund->codigo], 'status' => 'succeeded'];
        foreach ([['amount' => 1], ['livemode' => true], ['currency' => 'usd'], ['payment_intent' => 'pi_other']] as $change) {
            try {
                $service->applyStripe($refund, array_replace($result, $change));
                $this->fail('No se debe aceptar un resultado distinto.');
            } catch (RuntimeException) {
                $this->assertSame('procesando', $refund->fresh()->estado);
            }
        }
        $service->applyStripe($refund, $result, null, 'evt_1');
        $service->applyStripe($refund, $result, null, 'evt_1');
        $this->assertSame('completado', $refund->fresh()->estado);
        $this->assertSame(1, DB::table('eventos_reembolso')->where('stripe_event_id', 'evt_1')->count());
    }

    public function test_refund_event_is_reconciled_from_stripe_and_deduplicated(): void
    {
        [$patient, $payment] = $this->paid('stripe');
        $service = app(RefundManagement::class);
        $refund = $service->request($patient, $payment, 'Cancelación');
        $refund->forceFill(['estado' => 'procesando', 'iniciado_en' => now()])->save();
        $result = ['id' => 're_webhook', 'livemode' => false, 'payment_intent' => $payment->stripe_payment_intent,
            'amount' => 15000, 'currency' => 'bob', 'metadata' => ['reembolso_codigo' => $refund->codigo], 'status' => 'succeeded'];
        $this->mock(StripeGateway::class, fn ($mock) => $mock->shouldReceive('retrieveRefund')->twice()->with('re_webhook')->andReturn($result));
        $event = ['id' => 'evt_refund_1', 'type' => 'refund.updated', 'livemode' => false,
            'data' => ['object' => ['id' => 're_webhook', 'metadata' => ['reembolso_codigo' => $refund->codigo]]]];
        app(PaymentManagement::class)->webhook($event);
        app(PaymentManagement::class)->webhook($event);
        $this->assertSame('completado', $refund->fresh()->estado);
        $this->assertSame(1, DB::table('eventos_reembolso')->where('stripe_event_id', 'evt_refund_1')->count());
    }

    public function test_http_patient_and_reception_access_respects_permissions(): void
    {
        [$patient, $payment] = $this->paid('stripe');
        $this->post('/login', ['identificador' => $patient->correo, 'password' => 'password'])->assertRedirect();
        $this->get(route('pagos.show', $payment))->assertOk()->assertSee('Solicitar devolución');
        $this->get(route('reembolsos.index'))->assertForbidden();
        $this->post(route('reembolsos.store', $payment), ['motivo' => 'Cita cancelada'])->assertRedirect(route('pagos.show', $payment));
        $refund = ReembolsoConsulta::query()->where('pago_id', $payment->id)->firstOrFail();
        $this->post(route('reembolsos.decide', $refund), ['decision' => 'aprobar'])->assertForbidden();
        $this->post('/logout');
        $reception = $this->accountWithRole('recepcion');
        $this->post('/login', ['identificador' => $reception->correo, 'password' => 'password'])->assertRedirect();
        $page = $this->get(route('reembolsos.index'))->assertOk()->assertSee('Pendiente de revisión');
        if (getenv('REFUND_PREVIEW') === '1') {
            File::ensureDirectoryExists(public_path('build/refund-preview'));
            File::put(public_path('build/refund-preview/index.html'), $page->getContent());
        }
        $this->post(route('reembolsos.decide', $refund), ['decision' => 'aprobar'])->assertForbidden();
    }

    public function test_qr_refund_must_be_requested_through_reception(): void
    {
        [$patient, $payment] = $this->paid();
        $this->post('/login', ['identificador' => $patient->correo, 'password' => 'password'])->assertRedirect();
        $this->get(route('pagos.show', $payment))->assertOk()->assertSee('contacta a recepción');
        $this->post(route('reembolsos.store', $payment), ['motivo' => 'Reembolso'])->assertForbidden();
        $this->assertDatabaseMissing('reembolsos_consulta', ['pago_id' => $payment->id]);
    }
}
