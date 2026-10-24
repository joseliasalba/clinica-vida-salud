<?php

namespace App\Http\Controllers\Citas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Citas\ReprogramarCitaRequest;
use App\Models\CitaMedica;
use App\Services\Citas\CitaWorkflow;
use App\Services\Citas\ReprogramacionDisponibilidadService;
use App\Services\Citas\ReprogramacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReprogramacionController extends Controller
{
    public function edit(Request $request, CitaMedica $cita, CitaWorkflow $workflow, ReprogramacionDisponibilidadService $disponibilidad): View
    {
        $workflow->authorize($request->user(), $cita);
        $filters = $this->filters($request);
        $medicoId = (int) ($filters['medico_id'] ?? $cita->medico_id);
        $fecha = $filters['fecha'] ?? $cita->inicio->format('Y-m-d');
        $medicos = DB::table('medicos')->join('medico_especialidad', 'medico_especialidad.medico_id', '=', 'medicos.id')
            ->where('medico_especialidad.especialidad_id', $cita->especialidad_id)
            ->where('medico_especialidad.activo', true)->where('medicos.activo', true)
            ->orderBy('medicos.apellidos')->orderBy('medicos.nombres')
            ->get(['medicos.id', 'medicos.nombres', 'medicos.apellidos']);
        $turnos = $disponibilidad->turnos($medicoId, $cita->especialidad_id, $fecha, $cita->id, $cita->paciente_id);
        $cita->load(['paciente', 'especialidad']);

        return view('citas.reprogramar', compact('cita', 'medicos', 'medicoId', 'fecha', 'turnos'));
    }

    public function turnos(Request $request, CitaMedica $cita, CitaWorkflow $workflow, ReprogramacionDisponibilidadService $disponibilidad): JsonResponse
    {
        $workflow->authorize($request->user(), $cita);
        $filters = $request->validate(['medico_id' => ['required', 'integer', 'min:1'], 'fecha' => ['required', 'date_format:Y-m-d']]);

        return response()->json(['turnos' => $disponibilidad->turnos((int) $filters['medico_id'], $cita->especialidad_id, $filters['fecha'], $cita->id, $cita->paciente_id)]);
    }

    public function update(ReprogramarCitaRequest $request, CitaMedica $cita, ReprogramacionService $service): RedirectResponse
    {
        $service->reprogramar($request->user(), $cita->id, $request->validated());

        return redirect()->route('citas.gestion')->with('status', 'Cita reprogramada correctamente. La nueva fecha y el nuevo turno quedaron registrados.');
    }

    /** @return array<string, mixed> */
    private function filters(Request $request): array
    {
        return $request->validate(['medico_id' => ['nullable', 'integer', 'min:1'], 'fecha' => ['nullable', 'date_format:Y-m-d']]);
    }
}
