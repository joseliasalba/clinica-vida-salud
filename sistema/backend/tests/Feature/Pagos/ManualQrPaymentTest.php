<?php

namespace Tests\Feature\Pagos;

use App\Models\PagoConsulta;
use App\Services\Pagos\ManualQrPayment;
use App\Services\Pagos\PaymentManagement;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Tests\Support\CreatesAdministrativeUsers;
use Tests\Support\CreatesAppointments;
use Tests\TestCase;

class ManualQrPaymentTest extends TestCase
{
    use CreatesAdministrativeUsers, CreatesAppointments, DatabaseTransactions;

    private function image(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('qr.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/aN8AAAAASUVORK5CYII='));
    }

    private function setupPayment(): array
    {
        $patient = $this->accountWithRole('paciente');
        $patient->forceFill(['email_verified_at' => now()])->save();
        $appointment = $this->createAppointment(['estado' => 'pendiente_pago', 'creada_por' => $patient->id]);
        $appointment->paciente->forceFill(['usuario_id' => $patient->id])->save();
        $payment = PagoConsulta::factory()->create(['cita_id' => $appointment->id]);
        $admin = $this->accountWithRole('administrador');
        $manual = app(ManualQrPayment::class);
        $manual->saveQr($admin, $this->image());

        return [$patient, $payment->fresh(), $manual];
    }

    public function test_patient_reports_and_reception_confirms_exactly_once(): void
    {
        [$patient, $payment, $manual] = $this->setupPayment();
        $reception = $this->accountWithRole('recepcion');
        $manual->select($patient, $payment);
        $manual->report($patient, $payment, 'OPERACION-123');
        $this->assertSame('qr_revision', $payment->fresh()->estado);
        $this->assertSame('pendiente_pago', $payment->cita->fresh()->estado);
        $payment->forceFill(['vence_en' => now()->subHour()])->save();
        $manual->expire($payment);
        $this->assertSame('qr_revision', $payment->fresh()->estado);
        $manual->decide($reception, $payment, true, null);
        $this->assertSame('pagado', $payment->fresh()->estado);
        $this->assertSame('confirmada', $payment->cita->fresh()->estado);
        $this->assertDatabaseHas('eventos_pago_manual', ['pago_id' => $payment->id, 'usuario_id' => $reception->id, 'accion' => 'aprobado']);
        $this->expectException(ValidationException::class);
        $manual->decide($reception, $payment, true, null);
    }

    public function test_late_report_cannot_reclaim_an_expired_appointment(): void
    {
        [$patient, $payment, $manual] = $this->setupPayment();
        $manual->select($patient, $payment);
        $payment->forceFill(['vence_en' => now()->subMinute()])->save();
        $manual->expire($payment);
        $this->assertSame('cancelada', $payment->cita->fresh()->estado);
        $manual->report($patient, $payment, 'TARDIA-456');
        $manual->decide($this->accountWithRole('recepcion'), $payment, true, null);
        $this->assertSame('revision', $payment->fresh()->estado);
        $this->assertSame('cancelada', $payment->cita->fresh()->estado);
        $this->post('/login', ['identificador' => $patient->correo, 'password' => 'password'])->assertRedirect();
        $this->get(route('citas.receipt', $payment->cita))->assertRedirect(route('pagos.show', $payment));
    }

    public function test_payment_method_cannot_change_after_selecting_qr(): void
    {
        [$patient, $payment, $manual] = $this->setupPayment();
        $manual->select($patient, $payment);
        $this->expectException(ValidationException::class);
        app(PaymentManagement::class)->start($patient, $payment);
    }

    public function test_approval_after_the_appointment_starts_goes_to_manual_review(): void
    {
        [$patient, $payment, $manual] = $this->setupPayment();
        $manual->select($patient, $payment);
        $manual->report($patient, $payment, 'ATENCION-PASADA-1');
        $payment->cita->forceFill(['inicio' => now()->subHours(2), 'fin' => now()->subHour()])->save();
        $manual->decide($this->accountWithRole('recepcion'), $payment, true, null);
        $this->assertSame('revision', $payment->fresh()->estado);
        $this->assertSame('cancelada', $payment->cita->fresh()->estado);
    }

    public function test_receivers_are_authorized_and_rejections_are_audited(): void
    {
        [$patient, $payment, $manual] = $this->setupPayment();
        $manual->select($patient, $payment);
        $manual->report($patient, $payment, 'OPERACION-RECHAZADA');
        $this->post('/login', ['identificador' => $patient->correo, 'password' => 'password'])->assertRedirect();
        $this->get(route('pagos.qr.index'))->assertForbidden();
        $this->post(route('pagos.qr.decide', $payment), ['decision' => 'aprobar'])->assertForbidden();
        $this->post('/logout');
        $reception = $this->accountWithRole('recepcion');
        $this->post('/login', ['identificador' => $reception->correo, 'password' => 'password'])->assertRedirect();
        $this->get(route('pagos.qr.index'))->assertOk()->assertSee('OPERACION-RECHAZADA');
        $this->post(route('pagos.qr.decide', $payment), ['decision' => 'rechazar', 'motivo' => 'No figura en la cuenta'])->assertRedirect();
        $this->assertSame('qr_rechazado', $payment->fresh()->estado);
        $this->assertSame('cancelada', $payment->cita->fresh()->estado);
        $this->assertDatabaseHas('eventos_pago_manual', ['pago_id' => $payment->id, 'usuario_id' => $reception->id, 'accion' => 'rechazado']);
    }

    public function test_other_patients_cannot_access_or_report_another_payment(): void
    {
        [$patient, $payment, $manual] = $this->setupPayment();
        $manual->select($patient, $payment);
        $other = $this->accountWithRole('paciente');
        $other->forceFill(['email_verified_at' => now()])->save();
        $this->post('/login', ['identificador' => $other->correo, 'password' => 'password'])->assertRedirect();
        $this->get(route('pagos.qr.image', $payment))->assertForbidden();
        $this->post(route('pagos.qr.report', $payment), ['referencia' => 'AJENA'])->assertForbidden();
    }

    public function test_duplicate_operation_cannot_be_used_for_another_payment(): void
    {
        [$patient, $payment, $manual] = $this->setupPayment();
        $manual->select($patient, $payment);
        $manual->report($patient, $payment, 'UNICA-789');
        [$other, $second, $manual] = $this->setupPayment();
        $manual->select($other, $second);
        $this->expectException(ValidationException::class);
        $manual->report($other, $second, 'unica-789');
    }

    public function test_qr_rotation_keeps_the_selected_image_for_existing_payments(): void
    {
        [$patient, $payment, $manual] = $this->setupPayment();
        $manual->select($patient, $payment);
        $oldId = $payment->fresh()->qr_id;
        $manual->saveQr($this->accountWithRole('administrador'), $this->image());
        $this->assertNotSame($oldId, $manual->activeQr()->id);
        $this->assertDatabaseHas('codigos_qr_pago', ['id' => $oldId, 'activo' => false]);
        $this->assertSame($oldId, $payment->fresh()->qr_id);
    }

    public function test_patient_can_choose_qr_report_it_and_see_pending_confirmation(): void
    {
        [$patient, $payment] = $this->setupPayment();
        $this->post('/login', ['identificador' => $patient->correo, 'password' => 'password'])->assertRedirect();
        $this->get(route('pagos.show', $payment))->assertOk()->assertSee('Pagar por QR');
        $this->post(route('pagos.qr.select', $payment))->assertRedirect(route('pagos.show', $payment));
        $this->get(route('pagos.qr.image', $payment))->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->post(route('pagos.qr.report', $payment), ['referencia' => 'QR-123456'])->assertRedirect(route('pagos.show', $payment));
        $this->get(route('pagos.show', $payment))->assertOk()->assertSee('la cita todavía no está confirmada')->assertSee('QR-123456');
    }

    public function test_only_administrator_can_upload_qr(): void
    {
        $reception = $this->accountWithRole('recepcion');
        $this->post('/login', ['identificador' => $reception->correo, 'password' => 'password'])->assertRedirect();
        $this->post(route('pagos.qr.upload'), ['qr' => $this->image()])->assertForbidden();
        $this->post('/logout');
        $admin = $this->accountWithRole('administrador');
        $this->post('/login', ['identificador' => $admin->correo, 'password' => 'password'])->assertRedirect();
        $this->post(route('pagos.qr.upload'), ['qr' => $this->image()])->assertRedirect(route('pagos.qr.index'));
        $this->assertDatabaseHas('codigos_qr_pago', ['activo' => true, 'creado_por' => $admin->id]);
    }
}
