<?php

namespace Tests\Feature\Pagos;

use App\Models\PagoConsulta;
use App\Models\User;
use App\Services\Citas\AppointmentBooking;
use App\Services\Pagos\PaymentManagement;
use App\Services\Pagos\StripeGateway;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Mockery;
use RuntimeException;
use Tests\Support\CreatesAdministrativeUsers;
use Tests\Support\CreatesAppointments;
use Tests\TestCase;

class PaymentManagementTest extends TestCase
{
    use CreatesAdministrativeUsers, CreatesAppointments, DatabaseTransactions;

    private function payment(): PagoConsulta
    {
        $user = $this->accountWithRole('paciente');
        $user->forceFill(['email_verified_at' => now()])->save();
        $appointment = $this->createAppointment(['estado' => 'pendiente_pago', 'creada_por' => $user->id]);
        $appointment->paciente->forceFill(['usuario_id' => $user->id])->save();

        return PagoConsulta::factory()->create(['cita_id' => $appointment->id])->fresh();
    }

    private function checkoutSession(PagoConsulta $payment, array $changes = []): array
    {
        return array_replace([
            'id' => 'cs_test_'.$payment->codigo, 'livemode' => false, 'mode' => 'payment',
            'client_reference_id' => $payment->codigo, 'metadata' => ['pago_codigo' => $payment->codigo],
            'currency' => 'bob', 'amount_total' => 15000, 'status' => 'open', 'payment_status' => 'unpaid',
            'expires_at' => $payment->vence_en->timestamp, 'url' => 'https://checkout.stripe.com/c/pay/test',
            'payment_intent' => null, 'invoice' => null,
        ], $changes);
    }

    private function paid(PagoConsulta $payment): array
    {
        return $this->checkoutSession($payment, ['status' => 'complete', 'payment_status' => 'paid', 'payment_intent' => 'pi_'.$payment->codigo, 'invoice' => 'in_'.$payment->codigo, 'url' => null]);
    }

    private function loginOwner(PagoConsulta $payment): void
    {
        $this->post('/login', ['identificador' => User::findOrFail($payment->cita->paciente->usuario_id)->correo, 'password' => 'password'])->assertRedirect();
    }

    public function test_only_matching_paid_session_confirms_once_and_stale_events_never_reverse_it(): void
    {
        $payment = $this->payment();
        $service = app(PaymentManagement::class);
        $paid = $this->paid($payment);
        $service->apply($payment, $paid, 'evt_paid');
        $service->apply($payment, $paid, 'evt_paid');
        $service->apply($payment, $this->checkoutSession($payment, ['status' => 'expired']), 'evt_old');
        $this->assertSame('pagado', $payment->fresh()->estado);
        $this->assertSame('confirmada', $payment->cita->fresh()->estado);
        $this->assertSame(1, DB::table('cambios_cita')->where('accion', 'pago_confirmado')->count());
        $this->assertDatabaseCount('eventos_pago', 2);
    }

    public function test_wrong_amount_currency_identity_live_mode_and_free_payment_cannot_confirm(): void
    {
        $payment = $this->payment();
        foreach ([['amount_total' => 1], ['currency' => 'usd'], ['livemode' => true],
            ['metadata' => ['pago_codigo' => (string) Str::uuid()]], ['client_reference_id' => 'other'],
            ['mode' => 'subscription'], ['id' => 'cs_live_123'], ['status' => 'unknown']] as $change) {
            try {
                app(PaymentManagement::class)->apply($payment, array_replace($this->paid($payment), $change));
                $this->fail('Debe rechazar una sesión que no corresponde.');
            } catch (RuntimeException) {
                $this->assertSame('pendiente_pago', $payment->cita->fresh()->estado);
            }
        }
        app(PaymentManagement::class)->apply($payment, $this->checkoutSession($payment, ['status' => 'complete', 'payment_status' => 'no_payment_required']));
        $this->assertSame('procesando', $payment->fresh()->estado);
        $this->assertSame('pendiente_pago', $payment->cita->fresh()->estado);
    }

    public function test_expiration_releases_slot_and_late_payment_does_not_steal_new_booking(): void
    {
        $payment = $this->payment();
        $service = app(PaymentManagement::class);
        $service->apply($payment, $this->checkoutSession($payment, ['status' => 'expired']));
        $old = $payment->cita->fresh();
        $this->assertSame('cancelada', $old->estado);
        $replacement = $this->createAppointment(['medico_id' => $old->medico_id, 'especialidad_id' => $old->especialidad_id, 'horario_id' => $old->horario_id, 'inicio' => $old->inicio, 'fin' => $old->fin]);
        $service->apply($payment, $this->paid($payment), 'evt_late');
        $this->assertSame('revision', $payment->fresh()->estado);
        $this->assertSame('cancelada', $old->fresh()->estado);
        $this->assertSame('programada', $replacement->fresh()->estado);
    }

    public function test_async_payment_remains_unconfirmed_and_releases_after_its_deadline(): void
    {
        $payment = $this->payment();
        $session = $this->checkoutSession($payment, ['status' => 'complete']);
        app(PaymentManagement::class)->apply($payment, $session);
        $this->assertSame('pendiente_pago', $payment->cita->fresh()->estado);
        $this->travel(32)->minutes();
        app(PaymentManagement::class)->apply($payment, $session);
        $this->assertSame('vencido', $payment->fresh()->estado);
        $this->assertSame('cancelada', $payment->cita->fresh()->estado);
    }

    public function test_unstarted_expired_hold_releases_without_calling_stripe(): void
    {
        $payment = $this->payment();
        $this->mock(StripeGateway::class, fn ($mock) => $mock->shouldNotReceive('create', 'retrieve'));
        $this->travel(31)->minutes();
        app(PaymentManagement::class)->refresh($payment);
        $this->assertSame('vencido', $payment->fresh()->estado);
        $this->assertSame('cancelada', $payment->cita->fresh()->estado);
    }

    public function test_network_failure_preserves_hold_and_reuses_identical_request_on_retry(): void
    {
        $payment = $this->payment();
        $actor = User::findOrFail($payment->cita->paciente->usuario_id);
        $payload = null;
        $mock = Mockery::mock(StripeGateway::class);
        $mock->shouldReceive('create')->once()->andReturnUsing(function ($p) use (&$payload) {
            $payload = $p->checkout_payload;
            throw new RuntimeException('Simulated network failure');
        });
        $mock->shouldReceive('create')->once()->andReturnUsing(function ($p) use (&$payload, $payment) {
            $this->assertEquals($payload, $p->checkout_payload);
            $this->assertTrue($payload['invoice_creation']['enabled']);
            $this->assertSame(15000, $payload['line_items'][0]['price_data']['unit_amount']);
            $this->assertArrayNotHasKey('payment_method_types', $payload);

            return $this->checkoutSession($payment);
        });
        $service = new PaymentManagement($mock);
        try {
            $service->start($actor, $payment);
            $this->fail('Expected simulated failure');
        } catch (RuntimeException) {
            $this->assertSame('incierto', $payment->fresh()->estado);
            $this->assertSame('pendiente_pago', $payment->cita->fresh()->estado);
        }
        $service->start($actor, $payment->fresh());
        $this->assertSame('pendiente', $payment->fresh()->estado);
    }

    public function test_old_uncertain_attempt_never_recreates_a_checkout_after_idempotency_retention(): void
    {
        $payment = $this->payment();
        $payment->forceFill(['estado' => 'incierto', 'created_at' => now()->subHours(24)])->save();
        $this->mock(StripeGateway::class, fn ($mock) => $mock->shouldNotReceive('create'));
        $this->expectException(RuntimeException::class);
        app(PaymentManagement::class)->refresh($payment);
    }

    public function test_patient_access_is_owned_and_return_page_does_not_claim_payment(): void
    {
        $payment = $this->payment();
        $other = $this->payment();
        $this->loginOwner($payment);
        $this->mock(StripeGateway::class, fn ($mock) => $mock->shouldNotReceive('create', 'retrieve'));
        $this->get(route('pagos.show', $payment).'?success=true&session_id=cs_test_forged')->assertOk()->assertSee('Pendiente de iniciar');
        $this->get(route('pagos.show', $other))->assertForbidden();
        $this->post(route('pagos.start', $other))->assertForbidden();
        $this->post(route('pagos.refresh', $other))->assertForbidden();
        $this->get(route('pagos.invoice', $other))->assertForbidden();
        $this->get(route('citas.receipt', $payment->cita))->assertRedirect(route('pagos.show', $payment));
        $this->assertSame('pendiente_pago', $payment->cita->fresh()->estado);
    }

    public function test_webhook_requires_valid_signature_and_retrieves_authoritative_session(): void
    {
        $payment = $this->payment();
        config(['pagos.webhook_secret' => 'whsec_unit_test']);
        $this->partialMock(StripeGateway::class, function ($mock) use ($payment) {
            $mock->shouldReceive('retrieve')->once()->with('cs_test_event')->andReturn($this->paid($payment));
        });
        $event = ['id' => 'evt_signed', 'type' => 'checkout.session.completed', 'livemode' => false,
            'data' => ['object' => ['id' => 'cs_test_event', 'metadata' => ['pago_codigo' => $payment->codigo]]]];
        $body = json_encode($event, JSON_THROW_ON_ERROR);
        $timestamp = time();
        $signature = 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$body, 'whsec_unit_test');
        $this->call('POST', route('pagos.webhook'), [], [], [], ['CONTENT_TYPE' => 'application/json'], $body)->assertStatus(400);
        $this->call('POST', route('pagos.webhook'), [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => $signature], $body)->assertOk();
        $this->assertSame('pagado', $payment->fresh()->estado);
    }

    public function test_booking_uses_server_fee_and_duplicate_submit_does_not_create_second_payment(): void
    {
        config(['pagos.habilitado' => true]);
        $template = $this->payment();
        $appointment = $template->cita;
        $appointment->forceFill(['estado' => 'cancelada'])->save();
        $specialty = $appointment->especialidad;
        $specialty->forceFill(['tarifa_centavos' => 15000])->save();
        $actor = User::findOrFail($appointment->paciente->usuario_id);
        $data = ['especialidad_id' => $specialty->id, 'medico_id' => $appointment->medico_id,
            'horario_id' => $appointment->horario_id, 'fecha' => $appointment->inicio->toDateString(),
            'inicio' => $appointment->inicio->format('Y-m-d H:i:s')];
        $booking = app(AppointmentBooking::class);
        $review = $booking->review($actor, $data);
        $new = $booking->book($actor, $review['token']);
        $same = $booking->book($actor, $review['token']);
        $this->assertSame($new->id, $same->id);
        $this->assertSame('pendiente_pago', $new->estado);
        $this->assertDatabaseHas('pagos_consulta', ['cita_id' => $new->id, 'importe_centavos' => 15000]);
        $this->assertSame(1, PagoConsulta::query()->where('cita_id', $new->id)->count());
        $specialty->forceFill(['tarifa_centavos' => 20000])->save();
        $this->assertSame(15000, PagoConsulta::query()->where('cita_id', $new->id)->value('importe_centavos'));
    }

    public function test_changed_price_requires_new_review_before_holding_slot(): void
    {
        config(['pagos.habilitado' => true]);
        $payment = $this->payment();
        $appointment = $payment->cita;
        $appointment->forceFill(['estado' => 'cancelada'])->save();
        $appointment->especialidad->forceFill(['tarifa_centavos' => 15000])->save();
        $actor = User::findOrFail($appointment->paciente->usuario_id);
        $booking = app(AppointmentBooking::class);
        $review = $booking->review($actor, ['especialidad_id' => $appointment->especialidad_id, 'medico_id' => $appointment->medico_id, 'horario_id' => $appointment->horario_id, 'fecha' => $appointment->inicio->toDateString(), 'inicio' => $appointment->inicio->format('Y-m-d H:i:s')]);
        $appointment->especialidad->forceFill(['tarifa_centavos' => 18000])->save();
        $this->expectException(ValidationException::class);
        $booking->book($actor, $review['token']);
    }

    public function test_views_use_responsive_components_and_show_sandbox_and_exact_fee(): void
    {
        $payment = $this->payment();
        $this->loginOwner($payment);
        $list = $this->get(route('pagos.index'))->assertOk()->assertSee('150.00')->assertSee('no se realizan cobros reales');
        $detail = $this->get(route('pagos.show', $payment))->assertOk()->assertSee('tarjetas de prueba')->assertSee('150.00');
        if (getenv('PAYMENTS_PREVIEW') === '1') {
            File::ensureDirectoryExists(public_path('build/payments-preview'));
            File::put(public_path('build/payments-preview/index.html'), $list->getContent());
            File::put(public_path('build/payments-preview/show.html'), $detail->getContent());
        }
    }
}
