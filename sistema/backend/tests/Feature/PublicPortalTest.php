<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Support\CreatesAdministrativeUsers;
use Tests\TestCase;

class PublicPortalTest extends TestCase
{
    use CreatesAdministrativeUsers, DatabaseTransactions;

    public function test_public_home_explains_real_availability_and_links_to_account_flows(): void
    {
        $this->get('/')->assertOk()->assertViewIs('public.home')
            ->assertSee(route('login'))->assertSee(route('registro'))
            ->assertSee('Sacar ticket / reservar cita')->assertSee('Reserva en línea próximamente.')
            ->assertSee('Sitio de demostración')->assertSee(config('clinica.direccion'))
            ->assertDontSee('id="site-navigation"', false);
        $this->get('/inicio')->assertRedirect(route('login'));
    }

    public function test_registration_is_reachable_from_login_and_preserves_accessibility(): void
    {
        $this->get('/login')->assertOk()->assertSee(route('registro'))->assertSee(route('portada'));
        $this->get('/registro')->assertOk()->assertSee('name="_token"', false)
            ->assertSee('autocomplete="new-password"', false)->assertSee('minlength="8"', false)
            ->assertSee('name="fecha_nacimiento"', false)->assertSee('name="password_confirmation"', false)
            ->assertDontSee('name="roles', false);
    }

    public function test_authenticated_people_can_visit_the_site_but_not_create_another_account(): void
    {
        $this->loginManager();
        $this->get('/')->assertOk()->assertSee('Mi cuenta')->assertSee('data-navigation-open', false);
        $this->get('/registro')->assertRedirect(route('inicio'));
        $this->post('/registro')->assertRedirect(route('inicio'));
        $this->get('/inicio')->assertOk();
    }

    public function test_institutional_content_is_escaped_and_demo_notice_can_be_removed(): void
    {
        config(['clinica.datos_demostracion' => false, 'clinica.direccion' => '<script>alert(1)</script>']);
        $this->get('/')->assertOk()->assertSee('<script>alert(1)</script>')->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('Sitio de demostración')->assertDontSee('Especialidades de ejemplo');
    }
}
