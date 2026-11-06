<?php

namespace App\Http\Controllers\Citas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Citas\BookAppointmentRequest;
use App\Http\Requests\Citas\FindAppointmentRequest;
use App\Models\CitaMedica;
use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\PagoConsulta;
use App\Services\Citas\AppointmentAvailability;
use App\Services\Citas\AppointmentBooking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CitaController extends Controller
{
    public function entry(Request $request): RedirectResponse
    {
        if (! $request->user()) {
            $request->session()->put('booking.continue', true);

            return redirect()->route('login');
        }

        return redirect()->route('citas.index');
    }

    public function index(FindAppointmentRequest $request, AppointmentAvailability $availability, AppointmentBooking $booking): View|RedirectResponse
    {
        $manager = $request->user()->tienePermiso('citas.gestionar');
        if (! $manager && ! $request->user()->cumpleVerificacionParaAcceso()) {
            return redirect()->route('verification.notice');
        }
        $filters = $request->validated();
        $patient = $manager
            ? Paciente::query()->where('activo', true)->find($filters['paciente_id'] ?? 0)
            : Paciente::query()->where('activo', true)->where('usuario_id', $request->user()->id)->first();
        $patients = collect();
        if ($manager && ! empty($filters['paciente_q'])) {
            $term = '%'.addcslashes($filters['paciente_q'], '%_\\').'%';
            $patients = Paciente::query()->where('activo', true)->where(fn ($query) => $query->whereRaw("concat(nombres, ' ', apellidos) ILIKE ?", [$term])->orWhere('codigo', 'ilike', $term)->orWhere('ci', 'ilike', $term))->orderBy('apellidos')->limit(20)->get();
        }
        if ($manager && $patient && ! $patients->contains('id', $patient->id)) {
            $patients->prepend($patient);
        }
        $slots = collect();
        if (! empty($filters['especialidad_id']) && ($filters['accion'] ?? null) !== 'buscar_paciente') {
            try {
                $patient = $booking->patient($request->user(), isset($filters['paciente_id']) ? (int) $filters['paciente_id'] : null);
            } catch (ValidationException $exception) {
                return redirect()->route('citas.index')->withErrors($exception->errors());
            }
            $slots = $availability->slots((int) $filters['especialidad_id'], $filters['fecha'] ?? now()->toDateString(), ! empty($filters['medico_id']) ? (int) $filters['medico_id'] : null, $patient->id);
        }

        return view('citas.index', [
            'manager' => $manager, 'paciente' => $patient, 'pacientes' => $patients, 'turnos' => $slots, 'filters' => $filters,
            'especialidades' => Especialidad::query()->where('activo', true)->orderBy('nombre')->get(),
            'medicos' => Medico::query()->where('activo', true)->whereHas('especialidades', fn ($query) => $query->where('especialidades.activo', true)->where('medico_especialidad.activo', true)->when($filters['especialidad_id'] ?? null, fn ($query, $id) => $query->where('especialidades.id', $id)))->orderBy('apellidos')->get(),
        ]);
    }

    public function review(FindAppointmentRequest $request, AppointmentBooking $booking): View|RedirectResponse
    {
        if (! $request->user()->tienePermiso('citas.gestionar') && ! $request->user()->cumpleVerificacionParaAcceso()) {
            return redirect()->route('verification.notice');
        }

        try {
            return view('citas.review', $booking->review($request->user(), $request->validated()));
        } catch (ValidationException $exception) {
            return redirect()->route('citas.index')->withErrors($exception->errors());
        }
    }

    public function store(BookAppointmentRequest $request, AppointmentBooking $booking): RedirectResponse
    {
        try {
            $appointment = $booking->book($request->user(), $request->validated('token'));
        } catch (ValidationException $exception) {
            return redirect()->route('citas.index')->withErrors($exception->errors());
        }

        $payment = PagoConsulta::query()->where('cita_id', $appointment->id)->first();
        if ($payment) {
            return redirect()->route('pagos.show', $payment)->with('status', 'Cupo retenido temporalmente. Completa el pago para confirmar la cita.');
        }

        return redirect()->route('citas.receipt', $appointment)->with('status', 'Reserva registrada correctamente.');
    }

    public function receipt(Request $request, CitaMedica $cita): View|RedirectResponse
    {
        $manager = $request->user()->tienePermiso('citas.gestionar');
        abort_unless($manager || ($request->user()->tienePermiso('citas.propias') && $cita->paciente()->where('usuario_id', $request->user()->id)->exists()), 403);

        if ($cita->estado === 'pendiente_pago') {
            $payment = PagoConsulta::query()->where('cita_id', $cita->id)->firstOrFail();
            abort_if($manager, 409, 'Esta cita está pendiente de pago.');

            return redirect()->route('pagos.show', $payment);
        }

        return view('citas.receipt', ['cita' => $cita->load(['paciente', 'medico', 'especialidad'])]);
    }
}
