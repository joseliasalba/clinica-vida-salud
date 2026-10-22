<?php

namespace Tests\Feature;

use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesAdministrativeUsers;
use Tests\TestCase;

class SidebarNavigationTest extends TestCase
{
    use CreatesAdministrativeUsers, DatabaseTransactions;

    public function test_guests_do_not_receive_account_navigation(): void
    {
        $this->get('/login')->assertOk()->assertDontSee('id="site-navigation"', false)->assertDontSee('data-navigation-open', false);
    }

    #[DataProvider('roleNavigation')]
    public function test_navigation_contains_only_authorized_modules(string $role, array $routes, array $pending): void
    {
        $user = $this->accountWithRole($role);
        $this->post('/login', ['identificador' => $user->correo, 'password' => 'password'])->assertRedirect(route('inicio'));
        $xpath = $this->xpath($this->get('/inicio')->assertOk()->getContent());
        $links = [];
        foreach ($xpath->query('//dialog//nav//a') as $link) {
            $links[] = $link->getAttribute('href');
        }
        $this->assertSame(array_map(fn (string $route): string => route($route), $routes), $links);
        $upcoming = [];
        foreach ($xpath->query('//dialog//nav//*[@aria-disabled="true"]/span') as $item) {
            $upcoming[] = trim(str_replace('Próximamente', '', $item->textContent));
        }
        $this->assertSame($pending, $upcoming);
        $this->assertSame(0, $xpath->query('//dialog//nav//a[@href="#"]')->length);
        $this->assertSame(0, $xpath->query('//dialog//details[not(.//a) and not(.//*[@aria-disabled="true"])]')->length);
    }

    public static function roleNavigation(): array
    {
        return [
            'administrador' => ['administrador', ['inicio', 'pacientes.index', 'especialidades.index', 'usuarios.index', 'roles.index', 'permisos.index'], ['Médicos', 'Citas', 'Horarios de atención']],
            'recepción' => ['recepcion', ['inicio', 'pacientes.index'], ['Citas']],
            'médico' => ['medico', ['inicio'], ['Mi agenda']],
            'paciente' => ['paciente', ['inicio'], ['Mis citas', 'Mi perfil']],
        ];
    }

    public function test_drawer_has_accessible_controls_and_a_post_logout_form(): void
    {
        $this->loginManager();
        $xpath = $this->xpath($this->get('/inicio')->assertOk()->getContent());
        $this->assertSame(1, $xpath->query('//button[@data-navigation-open and @aria-controls="site-navigation" and @aria-haspopup="dialog" and @aria-expanded="false"]')->length);
        $this->assertSame(1, $xpath->query('//dialog[@id="site-navigation" and @aria-labelledby="navigation-title"]')->length);
        $this->assertSame(1, $xpath->query('//dialog//h2[@id="navigation-title"]')->length);
        $this->assertSame(1, $xpath->query('//dialog//form[@method="dialog"]/button[@aria-label="Cerrar menú" and @autofocus]')->length);
        $logout = $xpath->query('//dialog//form[@method="POST"]')->item(0);
        $this->assertNotNull($logout);
        $this->assertSame(route('logout'), $logout->getAttribute('action'));
        $this->assertSame(1, $xpath->query('.//input[@name="_token"]', $logout)->length);
    }

    public function test_current_module_is_marked_on_all_administrative_pages(): void
    {
        $this->loginManager();
        foreach (['/pacientes/crear' => 'pacientes.index', '/usuarios/crear' => 'usuarios.index', '/roles/crear' => 'roles.index', '/permisos' => 'permisos.index', '/especialidades/crear' => 'especialidades.index'] as $url => $route) {
            $xpath = $this->xpath($this->get($url)->assertOk()->getContent());
            $active = $xpath->query('//dialog//a[@aria-current="page"]');
            $this->assertSame(1, $active->length);
            $this->assertSame(route($route), $active->item(0)->getAttribute('href'));
            $this->assertSame(1, $xpath->query('ancestor::details[@open]', $active->item(0))->length);
        }
    }

    public function test_navigation_reflects_revoked_permissions_on_the_next_request(): void
    {
        $user = $this->loginManager();
        DB::table('usuario_rol')->where('usuario_id', $user->id)->update(['activo' => false]);
        $xpath = $this->xpath($this->get('/inicio')->assertOk()->getContent());
        $this->assertSame(1, $xpath->query('//dialog//nav//a')->length);
        $this->assertSame(0, $xpath->query('//dialog//details')->length);
        $this->get('/usuarios')->assertForbidden();
        $this->get('/pacientes')->assertForbidden();
    }

    private function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new DOMXPath($document);
    }
}
