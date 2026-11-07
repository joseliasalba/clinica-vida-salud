<?php

namespace Tests\Feature\Pagos;

use App\Models\PagoConsulta;
use App\Services\Pagos\RefundManagement;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Stripe\StripeClient;
use Tests\Support\CreatesAdministrativeUsers;
use Tests\Support\CreatesAppointments;
use Tests\TestCase;

class RefundStripeSandboxTest extends TestCase
{
    use CreatesAdministrativeUsers, CreatesAppointments, DatabaseTransactions;

    public function test_full_refund_against_stripe_sandbox_without_real_funds(): void
    {
        if (getenv('STRIPE_SANDBOX_CHECK') !== '1') {
            $this->markTestSkipped('Prueba externa optativa: STRIPE_SANDBOX_CHECK=1.');
        }
        $secret = (string) config('pagos.stripe_secret');
        $this->assertStringStartsWith('sk_test_', $secret);
        $stripe = new StripeClient($secret);
        $this->assertSame(config('pagos.account_id'), $stripe->accounts->retrieve()->id);
        $intent = $stripe->paymentIntents->create([
            'amount' => 5000, 'currency' => 'bob', 'payment_method' => 'pm_card_visa', 'confirm' => true,
            'automatic_payment_methods' => ['enabled' => true, 'allow_redirects' => 'never'],
            'metadata' => ['prueba' => 'cu19_reembolso_academico'],
        ], ['idempotency_key' => 'cu19-test-'.Str::uuid()]);
        $this->assertSame('succeeded', $intent->status);
        $this->assertFalse($intent->livemode);

        $patient = $this->accountWithRole('paciente');
        $patient->forceFill(['email_verified_at' => now()])->save();
        $appointment = $this->createAppointment(['estado' => 'cancelada', 'creada_por' => $patient->id]);
        $appointment->paciente->forceFill(['usuario_id' => $patient->id])->save();
        $payment = PagoConsulta::factory()->create(['cita_id' => $appointment->id, 'estado' => 'pagado', 'metodo' => 'stripe',
            'importe_centavos' => 5000, 'pagado_en' => now(), 'stripe_payment_intent' => $intent->id]);
        $admin = $this->accountWithRole('administrador');
        $service = app(RefundManagement::class);
        $refund = $service->request($patient, $payment, 'Prueba académica');
        $service->decide($admin, $refund, true, null);
        $service->processStripe($admin, $refund->fresh());
        $refund->refresh();
        $this->assertNotNull($refund->stripe_refund_id);
        $result = $stripe->refunds->retrieve($refund->stripe_refund_id);
        $this->assertSame(5000, $result->amount);
        $this->assertSame('bob', $result->currency);
        $this->assertContains($refund->estado, ['procesando', 'completado']);
        if ($result->status === 'succeeded') {
            $this->assertSame('completado', $refund->estado);
        }
    }
}
