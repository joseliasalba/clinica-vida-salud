<?php

namespace Tests\Feature\Pagos;

use App\Models\PagoConsulta;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesAppointments;
use Tests\TestCase;

class PaymentConcurrencyTest extends TestCase
{
    use CreatesAppointments;

    public function test_two_simultaneous_callbacks_confirm_exactly_once(): void
    {
        $appointment = $this->createAppointment(['estado' => 'pendiente_pago']);
        $payment = PagoConsulta::factory()->create(['cita_id' => $appointment->id]);
        $payload = ['payment_id' => $payment->id, 'session' => [
            'id' => 'cs_test_'.$payment->codigo, 'mode' => 'payment', 'livemode' => false,
            'metadata' => ['pago_codigo' => $payment->codigo], 'client_reference_id' => $payment->codigo,
            'amount_total' => 15000, 'currency' => 'bob', 'status' => 'complete', 'payment_status' => 'paid',
            'payment_intent' => 'pi_'.$payment->codigo,
        ]];
        $processes = [];
        try {
            for ($i = 0; $i < 2; $i++) {
                $process = proc_open([PHP_BINARY, '-c', dirname(base_path(), 2).'/.tools/php/php.ini', base_path('tests/Fixtures/Citas/payment-worker.php')], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());
                $this->assertIsResource($process);
                $processes[] = [$process, $pipes];
                fwrite($pipes[0], json_encode($payload)."\n");
                fflush($pipes[0]);
            }
            foreach ($processes as [$process, $pipes]) {
                $this->assertSame("READY\n", fgets($pipes[1]));
            }
            foreach ($processes as [$process, $pipes]) {
                fwrite($pipes[0], "GO\n");
                fclose($pipes[0]);
            }
            foreach ($processes as [$process, $pipes]) {
                $output = stream_get_contents($pipes[1]);
                $errors = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $this->assertSame(0, proc_close($process), $errors.$output);
                $this->assertSame(['estado' => 'pagado'], json_decode($output, true, flags: JSON_THROW_ON_ERROR));
            }
            $this->assertSame('confirmada', $appointment->fresh()->estado);
            $this->assertSame(1, DB::table('cambios_cita')->where('cita_id', $appointment->id)->count());
            $this->assertSame(1, DB::table('eventos_pago')->where('pago_id', $payment->id)->count());
        } finally {
            foreach ($processes as [$process, $pipes]) {
                if (is_resource($process)) {
                    proc_terminate($process);
                    proc_close($process);
                }
                foreach ($pipes as $pipe) {
                    if (is_resource($pipe)) {
                        fclose($pipe);
                    }
                }
            }
            DB::table('eventos_pago')->where('pago_id', $payment->id)->delete();
            $payment->delete();
            DB::table('cambios_cita')->where('cita_id', $appointment->id)->delete();
            $appointment->delete();
            DB::table('horarios_atencion')->where('id', $appointment->horario_id)->delete();
            DB::table('medico_especialidad')->where('medico_id', $appointment->medico_id)->delete();
            DB::table('medicos')->where('id', $appointment->medico_id)->delete();
            DB::table('especialidades')->where('id', $appointment->especialidad_id)->delete();
            DB::table('pacientes')->where('id', $appointment->paciente_id)->delete();
            DB::table('usuarios')->where('id', $appointment->creada_por)->delete();
        }
    }
}
