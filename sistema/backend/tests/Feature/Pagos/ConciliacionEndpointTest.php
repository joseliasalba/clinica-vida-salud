<?php

namespace Tests\Feature\Pagos;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ConciliacionEndpointTest extends TestCase
{
    public function test_it_requires_a_configured_secret_and_matching_bearer_token(): void
    {
        config(['pagos.conciliacion_secret' => null]);
        Artisan::shouldReceive('call')->never();
        $this->postJson(route('pagos.conciliar'))->assertUnauthorized();

        config(['pagos.conciliacion_secret' => str_repeat('a', 32)]);
        $this->postJson(route('pagos.conciliar'))->assertUnauthorized();
        $this->withToken(str_repeat('b', 32))->postJson(route('pagos.conciliar'))->assertUnauthorized();
    }

    public function test_it_runs_a_bounded_reconciliation_and_reports_failures(): void
    {
        config(['pagos.conciliacion_secret' => str_repeat('a', 32)]);
        Artisan::shouldReceive('call')->twice()->with('clinica:conciliar-pagos', ['--limit' => 1])->andReturn(0, 1);

        $this->withToken(str_repeat('a', 32))->postJson(route('pagos.conciliar'))->assertOk()->assertJson(['status' => 'ok']);
        $this->withToken(str_repeat('a', 32))->postJson(route('pagos.conciliar'))->assertStatus(503)->assertJson(['status' => 'pendiente']);
    }

    public function test_it_does_not_start_an_overlapping_reconciliation(): void
    {
        config(['pagos.conciliacion_secret' => str_repeat('a', 32)]);
        $lock = Cache::lock('cu18:conciliacion', 55);
        $this->assertTrue($lock->get());
        Artisan::shouldReceive('call')->never();

        try {
            $this->withToken(str_repeat('a', 32))->postJson(route('pagos.conciliar'))->assertStatus(409);
        } finally {
            $lock->release();
        }
    }
}
