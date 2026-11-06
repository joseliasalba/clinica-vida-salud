<?php

namespace App\Services\Pagos;

use App\Models\PagoConsulta;
use RuntimeException;
use Stripe\ApiRequestor;
use Stripe\HttpClient\CurlClient;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeGateway
{
    private function client(): StripeClient
    {
        $secret = (string) config('pagos.stripe_secret');
        if (! preg_match('/^(sk|rk)_test_/', $secret)) {
            throw new RuntimeException('Solo se permiten claves de prueba de Stripe.');
        }
        $http = new CurlClient;
        $http->setConnectTimeout(5);
        $http->setTimeout(15);
        ApiRequestor::setHttpClient($http);

        return new StripeClient(['api_key' => $secret, 'max_network_retries' => 1]);
    }

    public function create(PagoConsulta $payment): array
    {
        return $this->client()->checkout->sessions->create($payment->checkout_payload, ['idempotency_key' => 'cu18-'.$payment->codigo])->toArray();
    }

    public function retrieve(string $id): array
    {
        return $this->client()->checkout->sessions->retrieve($id, [])->toArray();
    }

    public function expire(string $id): array
    {
        return $this->client()->checkout->sessions->expire($id, [])->toArray();
    }

    public function invoice(string $id): array
    {
        return $this->client()->invoices->retrieve($id, [])->toArray();
    }

    public function event(string $body, string $signature): array
    {
        $secret = (string) config('pagos.webhook_secret');
        if ($secret === '') {
            throw new RuntimeException('Webhook no configurado.');
        }

        return Webhook::constructEvent($body, $signature, $secret)->toArray();
    }
}
