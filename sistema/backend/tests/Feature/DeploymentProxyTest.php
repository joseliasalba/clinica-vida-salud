<?php

namespace Tests\Feature;

use Illuminate\Http\Middleware\TrustProxies;
use Tests\TestCase;

class DeploymentProxyTest extends TestCase
{
    protected function tearDown(): void
    {
        TrustProxies::flushState();

        parent::tearDown();
    }

    public function test_trusted_proxy_preserves_https_in_login_and_registration_links(): void
    {
        TrustProxies::at(['10.0.0.2']);

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])
            ->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Forwarded-Port' => '443'])
            ->get('http://clinica.example.test/login')->assertOk()
            ->assertSee('action="https://clinica.example.test/login"', false)
            ->assertSee('href="https://clinica.example.test/registro"', false);
    }

    public function test_untrusted_peer_cannot_override_the_request_scheme(): void
    {
        TrustProxies::at(['10.0.0.2']);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
            ->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Forwarded-Port' => '443'])
            ->get('http://clinica.example.test/login')->assertOk()
            ->assertSee('action="http://clinica.example.test/login"', false);
    }

    public function test_forwarded_host_does_not_replace_the_application_host(): void
    {
        TrustProxies::at(['10.0.0.2']);

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])
            ->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Forwarded-Port' => '443', 'X-Forwarded-Host' => 'otro.example.test'])
            ->get('http://clinica.example.test/login')->assertOk()
            ->assertSee('action="https://clinica.example.test/login"', false)
            ->assertDontSee('otro.example.test');
    }
}
