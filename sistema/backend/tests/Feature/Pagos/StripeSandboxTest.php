<?php

namespace Tests\Feature\Pagos;

use App\Models\PagoConsulta;
use App\Services\Pagos\StripeGateway;
use Illuminate\Support\Str;
use Stripe\StripeClient;
use Tests\TestCase;

class StripeSandboxTest extends TestCase
{
    public function test_real_sandbox_can_create_idempotent_bob_checkout_with_invoice_and_expire_it(): void
    {
        if (getenv('STRIPE_SANDBOX_CHECK') !== '1') {
            $this->markTestSkipped('Prueba externa optativa: STRIPE_SANDBOX_CHECK=1.');
        }
        $secret = (string) config('pagos.stripe_secret');
        $this->assertStringStartsWith('sk_test_', $secret);
        $client = new StripeClient($secret);
        $this->assertSame(config('pagos.account_id'), $client->accounts->retrieve()->id);
        $id = (string) Str::uuid();
        $payment = new PagoConsulta(['codigo' => $id, 'checkout_payload' => [
            'mode' => 'payment', 'customer_creation' => 'always',
            'invoice_creation' => ['enabled' => true], 'adaptive_pricing' => ['enabled' => false],
            'client_reference_id' => $id, 'metadata' => ['pago_codigo' => $id, 'prueba' => 'cu18_integracion'],
            'expires_at' => time() + 1860,
            'line_items' => [['price_data' => ['currency' => 'bob', 'unit_amount' => 15000, 'product_data' => ['name' => 'Prueba técnica CU18 sin atención médica']], 'quantity' => 1]],
            'success_url' => 'http://127.0.0.1:8000/mis-pagos', 'cancel_url' => 'http://127.0.0.1:8000/mis-pagos',
        ]]);
        $gateway = app(StripeGateway::class);
        $session = $gateway->create($payment);
        try {
            $this->assertFalse($session['livemode']);
            $this->assertSame('bob', $session['currency']);
            $this->assertSame(15000, $session['amount_total']);
            $this->assertTrue($session['invoice_creation']['enabled']);
            $this->assertSame($session['id'], $gateway->create($payment)['id']);
            $this->assertSame('unpaid', $gateway->retrieve($session['id'])['payment_status']);
        } finally {
            $this->assertSame('expired', $gateway->expire($session['id'])['status']);
        }
    }
}
