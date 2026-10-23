<?php

namespace App\Http\Controllers\Citas;

use App\Http\Controllers\Controller;
use App\Models\CitaMedica;
use App\Models\Paciente;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HistorialController extends Controller
{
    public const ESTADOS = [
        'programada' => 'Programada', 'confirmada' => 'Confirmada',
        'en_curso' => 'En atención', 'completada' => 'Atendida',
        'cancelada' => 'Cancelada', 'inasistencia' => 'Inasistencia',
    ];

    public function index(Request $request, ?Paciente $paciente = null): View
    {
        $user = $request->user();
        $canSelectPatient = $user->tienePermiso('citas.gestionar') || $user->tienePermiso('pacientes.gestionar');
        abort_unless($canSelectPatient || $user->tienePermiso('citas.propias'), 403);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'paciente_id' => ['nullable', 'integer', 'min:1'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('desde'), ['after_or_equal:desde'])],
            'estado' => ['nullable', Rule::in(array_keys(self::ESTADOS))],
            'page' => ['nullable', 'integer', 'min:1'],
        ], [
            'hasta.after_or_equal' => 'La fecha final debe ser igual o posterior a la fecha inicial.',
            'desde.date_format' => 'La fecha inicial debe tener el formato año-mes-día.',
            'hasta.date_format' => 'La fecha final debe tener el formato año-mes-día.',
        ]);

        $patientId = $paciente?->id ?? ($filters['paciente_id'] ?? null);
        if ($canSelectPatient) {
            $selectedPatient = $patientId ? Paciente::query()->findOrFail($patientId) : null;
        } else {
            $selectedPatient = Paciente::query()->where('usuario_id', $user->id)->first();
            abort_if($patientId && (int) $patientId !== $selectedPatient?->id, 403);
            abort_if(! empty($filters['paciente_id']) && (int) $filters['paciente_id'] !== $selectedPatient?->id, 403);
        }

        $patients = collect();
        $hasMorePatients = false;
        if ($canSelectPatient) {
            $patientsQuery = Paciente::query()->select(['id', 'codigo', 'ci', 'nombres', 'apellidos', 'activo']);
            if (isset($filters['q']) && trim($filters['q']) !== '') {
                $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim($filters['q'])).'%';
                $patientsQuery->where(fn (Builder $query) => $query->where('codigo', 'ilike', $term)
                    ->orWhere('ci', 'ilike', $term)
                    ->orWhereRaw("concat_ws(' ', nombres, apellidos) ilike ?", [$term]));
            }
            $patients = $patientsQuery->orderBy('apellidos')->orderBy('nombres')->orderBy('id')->limit(26)->get();
            $hasMorePatients = $patients->count() > 25;
            $patients = $patients->take(25);
            if ($selectedPatient && ! $patients->contains('id', $selectedPatient->id)) {
                $patients->prepend($selectedPatient);
            }
        }

        $query = CitaMedica::query()
            ->select(['id', 'codigo', 'paciente_id', 'medico_id', 'especialidad_id', 'inicio', 'fin', 'estado', 'created_at'])
            ->with(['medico:id,nombres,apellidos', 'especialidad:id,nombre'])
            ->where('paciente_id', $selectedPatient?->id ?? 0);

        if (! empty($filters['desde'])) {
            $query->whereDate('inicio', '>=', $filters['desde']);
        }
        if (! empty($filters['hasta'])) {
            $query->whereDate('inicio', '<=', $filters['hasta']);
        }
        if (! empty($filters['estado'])) {
            $query->whereIn('estado', $filters['estado'] === 'completada' ? ['completada', 'atendida'] : [$filters['estado']]);
        }
        $citas = $query->orderByDesc('inicio')->orderByDesc('id')->paginate(15)->withQueryString();

        return view('historial.index', [
            'canSelectPatient' => $canSelectPatient, 'paciente' => $selectedPatient,
            'pacientes' => $patients, 'hasMorePatients' => $hasMorePatients,
            'filters' => $filters, 'citas' => $citas, 'estados' => self::ESTADOS,
        ]);
    }
}
