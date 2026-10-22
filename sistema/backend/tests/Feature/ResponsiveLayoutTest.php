<?php

namespace Tests\Feature;

use App\Models\Paciente;
use App\Models\User;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\View\ViewException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\Support\CreatesAdministrativeUsers;
use Tests\TestCase;

class ResponsiveLayoutTest extends TestCase
{
    use CreatesAdministrativeUsers, DatabaseTransactions;

    public function test_every_administrative_list_keeps_all_cells_and_mobile_labels_in_one_accessible_table(): void
    {
        $this->loginManager();
        Paciente::factory()->create(['ci' => '001234AB', 'nombres' => 'María de los Ángeles', 'apellidos' => str_repeat('Apellido ', 12)]);
        User::factory()->create(['correo' => str_repeat('correo', 35).'@example.test']);
        foreach (['/pacientes', '/usuarios', '/roles', '/permisos'] as $url) {
            $response = $this->get($url)->assertOk();
            $xpath = $this->xpath($response->getContent());
            $this->assertSame(1, $xpath->query('//table[@role="table"]')->length, $url);
            $table = $xpath->query('//table')->item(0);
            $this->assertNotEmpty($table->getAttribute('aria-label'));
            $headers = [];
            foreach ($xpath->query('./thead/tr/th', $table) as $header) {
                $this->assertSame('col', $header->getAttribute('scope'));
                $headers[$header->getAttribute('data-column')] = trim($header->textContent);
            }
            $this->assertNotEmpty($headers);
            foreach ($xpath->query('./tbody/tr', $table) as $row) {
                $this->assertSame('row', $row->getAttribute('role'));
                $columns = [];
                foreach ($xpath->query('./th|./td', $row) as $cell) {
                    $key = $cell->getAttribute('data-column');
                    $columns[] = $key;
                    $label = $xpath->query('./span[@class="table-cell-label"]', $cell)->item(0);
                    $this->assertInstanceOf(DOMElement::class, $label);
                    $this->assertSame($headers[$key], trim($label->textContent));
                    $this->assertSame('true', $label->getAttribute('aria-hidden'));
                    $this->assertSame(1, $xpath->query('./div[@class="table-cell-value"]', $cell)->length);
                    $this->assertContains($cell->getAttribute('role'), ['cell', 'rowheader']);
                }
                $this->assertSame(array_keys($headers), $columns, 'No se puede omitir una columna en la versión móvil: '.$url);
            }
        }
    }

    public function test_patient_phone_and_both_actions_remain_present_with_long_data(): void
    {
        $this->loginManager();
        $patient = Paciente::factory()->create(['ci' => str_repeat('A', 45), 'nombres' => str_repeat('Nombre ', 20), 'telefono' => '+591 71234567']);
        $response = $this->get('/pacientes')->assertOk()->assertSee($patient->nombres)->assertSee($patient->ci)->assertSee($patient->telefono);
        $xpath = $this->xpath($response->getContent());
        $this->assertSame(1, $xpath->query('//tbody/tr/td[@data-column="telefono"]')->length);
        $this->assertSame(2, $xpath->query('//tbody/tr/td[@data-column="acciones"]//a')->length);
        $response->assertSee(route('pacientes.show', $patient))->assertSee(route('pacientes.edit', $patient));
    }

    public function test_empty_table_spans_all_declared_columns_without_fake_mobile_labels(): void
    {
        $this->loginManager();
        $response = $this->get('/pacientes')->assertOk()->assertSee('No encontramos pacientes');
        $xpath = $this->xpath($response->getContent());
        $cell = $xpath->query('//table/tbody/tr/td[@class="table-empty"]')->item(0);
        $this->assertInstanceOf(DOMElement::class, $cell);
        $this->assertSame((string) $xpath->query('//table/thead/tr/th')->length, $cell->getAttribute('colspan'));
        $this->assertSame(0, $xpath->query('.//span[@class="table-cell-label"]', $cell)->length);
    }

    public function test_unknown_cell_column_fails_instead_of_silently_losing_its_mobile_label(): void
    {
        $this->expectException(ViewException::class);
        $this->expectExceptionMessage('La celda debe usar una columna declarada');
        $this->blade('<x-responsive-table label="Prueba" :columns="$columns"><tr role="row"><x-table-cell column="inexistente">Dato</x-table-cell></tr></x-responsive-table>', ['columns' => ['nombre' => 'Nombre']]);
    }

    public function test_new_views_must_use_the_shared_table_instead_of_an_unadapted_raw_table(): void
    {
        $views = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path('../frontend/views')));
        foreach ($views as $view) {
            if (! $view->isFile() || ! str_ends_with($view->getFilename(), '.blade.php') || $view->getFilename() === 'responsive-table.blade.php') {
                continue;
            }
            $this->assertDoesNotMatchRegularExpression('/<table\b/i', file_get_contents($view->getPathname()), 'Usa x-responsive-table en '.$view->getPathname());
        }
    }

    private function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument;
        $document->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);

        return new DOMXPath($document);
    }
}
