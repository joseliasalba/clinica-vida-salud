<?php

namespace Tests\Feature\Estudios;

use App\Models\Paciente;
use App\Models\ResultadoEstudio;
use App\Models\SolicitudEstudio;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesAdministrativeUsers;
use Tests\TestCase;

class StudyManagementTest extends TestCase
{
    use CreatesAdministrativeUsers, DatabaseTransactions;

    public function test_authentication_is_required(): void
    {
        foreach (['/estudios', '/estudios/solicitar', '/estudios/1', '/estudios/1/archivo'] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
        $this->post('/estudios', [])->assertRedirect(route('login'));
        $this->put('/estudios/1/resultado', [])->assertRedirect(route('login'));
        $this->patch('/estudios/1/estado', [])->assertRedirect(route('login'));
    }

    public function test_doctor_requests_a_study_from_their_consultation_and_patient_cannot_be_overridden(): void
    {
        [$doctor, $study] = $this->context();
        $this->signIn($doctor);
        $this->get('/estudios/solicitar')->assertOk();
        $this->post('/estudios', [
            'consulta_id' => $study->consulta_id, 'nombre_estudio' => 'Hemograma de prueba',
            'indicaciones' => 'Indicaciones de prueba', 'paciente_id' => 999999, 'estado' => 'completado',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $created = SolicitudEstudio::query()->latest('id')->first();
        $this->assertSame($study->consulta_id, $created->consulta_id);
        $this->assertSame('solicitado', $created->estado);
        $this->assertDatabaseHas('eventos_estudio', ['solicitud_id' => $created->id, 'usuario_id' => $doctor->id, 'accion' => 'SOLICITAR']);
        $this->get('/estudios/'.$created->id)->assertOk()->assertSee('Hemograma de prueba');
    }

    public function test_foreign_consultations_and_studies_are_inaccessible_to_other_doctors(): void
    {
        [$owner, $study] = $this->context();
        [$other] = $this->context();
        $this->signIn($other);
        $this->get('/estudios')->assertOk()->assertDontSee($study->nombre_estudio);
        $this->get('/estudios/'.$study->id)->assertNotFound();
        $this->post('/estudios', ['consulta_id' => $study->consulta_id, 'nombre_estudio' => 'Ajeno'])->assertNotFound();
        $this->put('/estudios/'.$study->id.'/resultado', $this->payload($study))->assertNotFound();
        $this->patch('/estudios/'.$study->id.'/estado', ['version' => $study->editVersion(), 'estado' => 'cancelado', 'motivo' => 'Prueba', 'confirmacion' => '1'])->assertNotFound();
    }

    public function test_admin_and_reception_have_no_implicit_clinical_access(): void
    {
        [$doctor, $study] = $this->context();
        foreach (['administrador', 'recepcion'] as $role) {
            $actor = $this->accountWithRole($role);
            $this->signIn($actor);
            $this->get('/estudios')->assertForbidden();
            $this->get('/estudios/'.$study->id)->assertForbidden();
            $this->get('/estudios/'.$study->id.'/archivo')->assertForbidden();
            $this->post('/estudios', ['consulta_id' => $study->consulta_id, 'nombre_estudio' => 'Prueba'])->assertForbidden();
            $this->put('/estudios/'.$study->id.'/resultado', $this->payload($study))->assertForbidden();
        }
    }

    public function test_patient_sees_only_own_published_results_and_never_draft_contents_or_file_paths(): void
    {
        [$doctor, $study, $patient] = $this->context();
        ResultadoEstudio::factory()->create(['solicitud_id' => $study->id, 'descripcion' => 'BORRADOR PRIVADO', 'archivo_privado' => 'oculto.pdf']);
        $this->signIn($patient);
        $this->get('/estudios')->assertOk()->assertSee($study->nombre_estudio)->assertDontSee('BORRADOR PRIVADO');
        $this->get('/estudios/'.$study->id)->assertOk()->assertDontSee('BORRADOR PRIVADO')->assertDontSee('oculto.pdf')->assertDontSee('Cargar resultado');
        $this->get('/estudios/'.$study->id.'/archivo')->assertNotFound();
        $this->post('/estudios', [])->assertForbidden();
        $this->put('/estudios/'.$study->id.'/resultado', $this->payload($study))->assertForbidden();
        [$otherDoctor, $foreign] = $this->context();
        $this->get('/estudios/'.$foreign->id)->assertNotFound();
        $this->get('/estudios/'.$foreign->id.'/archivo')->assertNotFound();
        $this->get('/estudios?paciente_id=999999')->assertOk()->assertDontSee($foreign->nombre_estudio);
    }

    public function test_publish_download_retire_and_correct_workflow(): void
    {
        Storage::fake('estudios');
        [$doctor, $study, $patient] = $this->context();
        $this->signIn($doctor);
        $file = UploadedFile::fake()->createWithContent('resultado.pdf', "%PDF-1.4\nContenido ficticio\n%%EOF");
        $this->put('/estudios/'.$study->id.'/resultado', $this->payload($study, ['archivo' => $file]))
            ->assertSessionHasNoErrors()->assertRedirect();
        $result = $study->fresh()->resultado;
        $this->assertSame('publicado', $result->estado_publicacion);
        $this->assertSame($doctor->id, $result->publicado_por);
        $this->assertNotNull($result->fecha_publicacion);
        $this->assertSame('completado', $study->fresh()->estado);
        Storage::disk('estudios')->assertExists($result->archivo_privado);
        $this->signIn($patient);
        $this->get('/estudios/'.$study->id)->assertOk()->assertSee('Resultado ficticio')->assertSee('Descargar resultado PDF');
        $this->get('/estudios/'.$study->id.'/archivo')->assertOk()->assertDownload('resultado-estudio-'.$study->id.'.pdf')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->signIn($doctor);
        $this->put('/estudios/'.$study->id.'/resultado', $this->payload($study->fresh(), ['descripcion' => 'Cambio directo']))->assertSessionHasErrors('accion');
        $this->put('/estudios/'.$study->id.'/resultado', $this->payload($study->fresh(), ['accion' => 'retirar', 'motivo' => 'Revisión necesaria']))->assertSessionHasNoErrors();
        $this->assertSame('retirado', $result->fresh()->estado_publicacion);
        $this->assertSame('en_proceso', $study->fresh()->estado);
        $this->signIn($patient);
        $this->get('/estudios/'.$study->id)->assertOk()->assertDontSee('Resultado ficticio');
        $this->get('/estudios/'.$study->id.'/archivo')->assertNotFound();
        $this->signIn($doctor);
        $this->put('/estudios/'.$study->id.'/resultado', $this->payload($study->fresh(), ['descripcion' => 'Resultado corregido']))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('resultados_estudio', 1);
        $this->assertDatabaseHas('eventos_estudio', ['solicitud_id' => $study->id, 'accion' => 'RETIRAR', 'motivo' => 'Revisión necesaria']);
        $this->signIn($patient);
        $this->get('/estudios/'.$study->id)->assertOk()->assertSee('Resultado corregido');
    }

    public function test_drafts_are_saved_without_publishing_and_can_be_published_later(): void
    {
        [$doctor, $study, $patient] = $this->context();
        $this->signIn($doctor);
        $this->put('/estudios/'.$study->id.'/resultado', $this->payload($study, ['accion' => 'borrador']))->assertSessionHasNoErrors();
        $this->assertSame('en_proceso', $study->fresh()->estado);
        $result = $study->fresh()->resultado;
        $this->assertSame('borrador', $result->estado_publicacion);
        $this->assertNull($result->fecha_publicacion);
        $this->assertNull($result->publicado_por);
        $this->put('/estudios/'.$study->id.'/resultado', $this->payload($study->fresh()))->assertSessionHasNoErrors();
        $this->assertSame('publicado', $result->fresh()->estado_publicacion);
    }

    public function test_stale_forms_do_not_overwrite_or_duplicate_results_or_audits(): void
    {
        [$doctor, $study] = $this->context();
        $this->signIn($doctor);
        $stale = $this->payload($study);
        $this->put('/estudios/'.$study->id.'/resultado', $this->payload($study, ['accion' => 'borrador']))->assertSessionHasNoErrors();
        $count = DB::table('eventos_estudio')->count();
        $this->put('/estudios/'.$study->id.'/resultado', $stale)->assertSessionHasErrors('version');
        $this->assertSame($count, DB::table('eventos_estudio')->count());
        $this->assertSame('borrador', $study->fresh()->resultado->estado_publicacion);
    }

    public function test_validation_rejects_invalid_empty_oversized_results_and_requires_confirmation(): void
    {
        Storage::fake('estudios');
        [$doctor, $study] = $this->context();
        $this->signIn($doctor);
        foreach ([
            [['descripcion' => '', 'archivo' => null], 'descripcion'],
            [['descripcion' => []], 'descripcion'],
            [['descripcion' => str_repeat('x', 20001)], 'descripcion'],
            [['version' => 'incorrecta'], 'version'],
            [['accion' => 'completado'], 'accion'],
            [['confirmacion' => '0'], 'confirmacion'],
            [['accion' => 'retirar', 'motivo' => ''], 'motivo'],
            [['archivo' => UploadedFile::fake()->create('malicioso.html', 1, 'text/html')], 'archivo'],
            [['archivo' => UploadedFile::fake()->create('grande.pdf', 2049, 'application/pdf')], 'archivo'],
        ] as [$changes, $field]) {
            $this->put('/estudios/'.$study->id.'/resultado', $this->payload($study->fresh(), $changes))->assertSessionHasErrors($field);
        }
        $this->assertDatabaseCount('resultados_estudio', 0);
        $this->assertDatabaseCount('eventos_estudio', 0);
        $this->assertSame([], Storage::disk('estudios')->allFiles());
        $this->post('/estudios', ['consulta_id' => [], 'nombre_estudio' => []])->assertSessionHasErrors(['consulta_id', 'nombre_estudio']);
    }

    public function test_failed_upload_transaction_removes_new_private_file(): void
    {
        Storage::fake('estudios');
        [$doctor, $study] = $this->context();
        $this->signIn($doctor);
        DB::statement("ALTER TABLE eventos_estudio ADD CONSTRAINT pruebas_fallo_auditoria CHECK (accion <> 'PUBLICAR')");
        $this->withoutExceptionHandling();
        try {
            $this->put('/estudios/'.$study->id.'/resultado', $this->payload($study, ['archivo' => UploadedFile::fake()->createWithContent('resultado.pdf', "%PDF-1.4\nPrueba\n%%EOF")]));
            $this->fail('La auditoría debía fallar.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('pruebas_fallo_auditoria', $exception->getMessage());
        }
        $this->assertDatabaseCount('resultados_estudio', 0);
        $this->assertSame('solicitado', $study->fresh()->estado);
        $this->assertSame([], Storage::disk('estudios')->allFiles());
    }

    public function test_cancellation_requires_reason_and_closed_studies_cannot_be_changed(): void
    {
        [$doctor, $study] = $this->context();
        $this->signIn($doctor);
        $data = ['estado' => 'cancelado', 'version' => $study->editVersion(), 'confirmacion' => '1'];
        $this->patch('/estudios/'.$study->id.'/estado', $data)->assertSessionHasErrors('motivo');
        $this->patch('/estudios/'.$study->id.'/estado', $data + ['motivo' => 'Solicitud duplicada'])->assertSessionHasNoErrors();
        $this->assertSame('cancelado', $study->fresh()->estado);
        $this->put('/estudios/'.$study->id.'/resultado', $this->payload($study->fresh()))->assertSessionHasErrors('accion');
        $this->patch('/estudios/'.$study->id.'/estado', array_replace($data, ['estado' => 'en_proceso', 'version' => $study->fresh()->editVersion()]))->assertSessionHasErrors('estado');
    }

    public function test_permissions_and_professional_link_are_rechecked_on_submission(): void
    {
        [$doctor, $study] = $this->context();
        $this->signIn($doctor);
        DB::table('medicos')->where('usuario_id', $doctor->id)->update(['activo' => false]);
        $this->put('/estudios/'.$study->id.'/resultado', $this->payload($study))->assertForbidden();
        DB::table('medicos')->where('usuario_id', $doctor->id)->update(['activo' => true]);
        DB::table('permisos')->where('codigo', 'estudios.publicar')->update(['activo' => false]);
        $this->put('/estudios/'.$study->id.'/resultado', $this->payload($study))->assertForbidden();
        $this->assertDatabaseCount('resultados_estudio', 0);
    }

    public function test_unverified_or_unlinked_patient_cannot_read_results(): void
    {
        [$doctor, $study, $patient] = $this->context();
        $patient->forceFill(['email_verified_at' => null])->save();
        $this->signIn($patient);
        $this->get('/estudios')->assertRedirect(route('verification.notice'));
        $this->get('/estudios/'.$study->id)->assertRedirect(route('verification.notice'));
        $this->get('/estudios/'.$study->id.'/archivo')->assertRedirect(route('verification.notice'));
        $patient->forceFill(['email_verified_at' => now()])->save();
        DB::table('pacientes')->where('usuario_id', $patient->id)->update(['usuario_id' => null]);
        $this->signIn($patient);
        $this->get('/estudios/'.$study->id)->assertForbidden();
    }

    public function test_results_are_paginated_filtered_and_do_not_render_raw_html(): void
    {
        [$doctor, $study] = $this->context();
        SolicitudEstudio::factory()->count(16)->create(['consulta_id' => $study->consulta_id, 'nombre_estudio' => '<script>alert(1)</script>']);
        $this->signIn($doctor);
        $this->get('/estudios')->assertOk()->assertViewHas('estudios', fn ($rows) => $rows->total() === 17 && $rows->count() === 15)->assertDontSee('<script>alert(1)</script>', false);
        $this->get('/estudios?estado=cancelado')->assertOk()->assertViewHas('estudios', fn ($rows) => $rows->total() === 0);
        $this->get('/estudios?estado[]=solicitado')->assertSessionHasErrors('estado');
        $this->get('/estudios/solicitar?q[]=x')->assertSessionHasErrors('q');
    }

    public function test_processing_requires_a_valid_transition_and_a_current_form(): void
    {
        [$doctor, $study] = $this->context();
        $this->signIn($doctor);
        $data = ['estado' => 'en_proceso', 'version' => $study->editVersion(), 'confirmacion' => '1'];
        $this->patch('/estudios/'.$study->id.'/estado', $data)->assertSessionHasNoErrors();
        $this->assertSame('en_proceso', $study->fresh()->estado);
        $this->patch('/estudios/'.$study->id.'/estado', $data)->assertSessionHasErrors('version');
        $data['version'] = $study->fresh()->editVersion();
        $this->patch('/estudios/'.$study->id.'/estado', $data)->assertSessionHasErrors('estado');
        $this->assertDatabaseCount('eventos_estudio', 1);
    }

    public function test_inconsistent_or_canceled_consultations_are_not_available(): void
    {
        [$doctor, $study] = $this->context();
        $this->signIn($doctor);
        $consultation = DB::table('consultas_medicas')->find($study->consulta_id);
        DB::table('citas_medicas')->where('id', $consultation->cita_id)->update(['estado' => 'cancelada']);
        $this->post('/estudios', ['consulta_id' => $study->consulta_id, 'nombre_estudio' => 'Prueba'])->assertNotFound();
        $this->get('/estudios/'.$study->id)->assertNotFound();
        DB::table('citas_medicas')->where('id', $consultation->cita_id)->update(['estado' => 'completada']);
        DB::table('consultas_medicas')->where('id', $study->consulta_id)->update(['fecha_atencion' => now()->addDay()]);
        $this->post('/estudios', ['consulta_id' => $study->consulta_id, 'nombre_estudio' => 'Prueba'])->assertNotFound();
    }

    public function test_database_rejects_duplicate_results_and_incomplete_publications(): void
    {
        [$doctor, $study] = $this->context();
        ResultadoEstudio::factory()->create(['solicitud_id' => $study->id]);
        foreach ([
            fn () => ResultadoEstudio::factory()->create(['solicitud_id' => $study->id]),
            fn () => $study->resultado->update(['estado_publicacion' => 'publicado']),
            fn () => $study->update(['estado' => 'invalido']),
        ] as $operation) {
            try {
                DB::transaction($operation);
                $this->fail('La base de datos debía rechazar el cambio.');
            } catch (QueryException $exception) {
                $this->assertContains($exception->getCode(), ['23505', '23514']);
            }
        }
        $this->assertSame('borrador', $study->fresh()->resultado->estado_publicacion);
    }

    public function test_study_views_use_shared_mobile_labels_with_long_data(): void
    {
        [$doctor, $study, $patient] = $this->context();
        $study->update(['nombre_estudio' => str_repeat('Estudio de laboratorio ', 10), 'indicaciones' => str_repeat('Indicaciones de prueba. ', 25)]);
        $this->signIn($doctor);
        $pages = [
            'index' => $this->get('/estudios')->assertOk()->getContent(),
            'create' => $this->get('/estudios/solicitar')->assertOk()->getContent(),
            'draft' => $this->get('/estudios/'.$study->id)->assertOk()->getContent(),
        ];
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$pages['index']);
        $xpath = new \DOMXPath($document);
        $this->assertSame(6, $xpath->query('//table/thead/tr/th')->length);
        $this->assertSame(6, $xpath->query('//table/tbody/tr/*/span[@class="table-cell-label"]')->length);
        $this->put('/estudios/'.$study->id.'/resultado', $this->payload($study->fresh(), ['descripcion' => str_repeat('Resultado ficticio para revisión. ', 40)]))->assertSessionHasNoErrors();
        $pages['published'] = $this->get('/estudios/'.$study->id)->assertOk()->getContent();
        $this->signIn($patient);
        $pages['patient'] = $this->get('/estudios/'.$study->id)->assertOk()->getContent();
        if (getenv('CU16_PREVIEW') === '1') {
            File::ensureDirectoryExists(public_path('build/cu16-preview'));
            foreach ($pages as $name => $html) {
                File::put(public_path('build/cu16-preview/'.$name.'.html'), $html);
            }
        }
    }

    /** @return array{User, SolicitudEstudio, User} */
    private function context(): array
    {
        $doctor = $this->accountWithRole('medico');
        $patient = $this->accountWithRole('paciente');
        $patient->markEmailAsVerified();
        $study = SolicitudEstudio::factory()->create(['nombre_estudio' => 'Estudio-'.fake()->uuid()]);
        $consultation = DB::table('consultas_medicas')->find($study->consulta_id);
        DB::table('medicos')->where('id', $consultation->medico_id)->update(['usuario_id' => $doctor->id]);
        $patientId = DB::table('historias_clinicas')->where('id', $consultation->historia_id)->value('paciente_id');
        Paciente::query()->whereKey($patientId)->update(['usuario_id' => $patient->id]);

        return [$doctor, $study, $patient];
    }

    private function signIn(User $user): void
    {
        $this->actingAs($user)->withSession([
            'auth_last_activity' => now()->timestamp,
            'auth_password_hash' => $user->getAuthPassword(),
            'auth_session_token' => $user->remember_token ?? '',
        ]);
    }

    private function payload(SolicitudEstudio $study, array $changes = []): array
    {
        return array_replace([
            'version' => $study->editVersion(), 'descripcion' => 'Resultado ficticio',
            'accion' => 'publicar', 'confirmacion' => '1',
        ], $changes);
    }
}
