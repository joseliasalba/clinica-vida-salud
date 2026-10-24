<?php

namespace App\Http\Controllers\Citas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Citas\CambiarEstadoCitaRequest;
use App\Models\CitaMedica;
use App\Services\Citas\AsistenciaService;
use App\Services\Citas\CitaWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AsistenciaController extends Controller
{
    public function edit(Request $request, CitaMedica $cita, CitaWorkflow $workflow): View
    {
        $workflow->authorize($request->user(), $cita, true);
        $cita->load(['paciente', 'especialidad']);
        $estados = [];
        foreach (['confirmada' => 'Confirmada', 'completada' => 'Atendida', 'inasistencia' => 'Inasistencia'] as $value => $label) {
            try {
                $workflow->assertTransition($cita, $value);
                $estados[$value] = $label;
            } catch (ValidationException) {
                continue;
            }
        }

        return view('citas.asistencia', compact('cita', 'estados'));
    }

    public function update(CambiarEstadoCitaRequest $request, CitaMedica $cita, AsistenciaService $service): RedirectResponse
    {
        $cita = $service->changeState($request->user(), $cita->id, $request->validated());
        $labels = ['confirmada' => 'confirmada', 'completada' => 'atendida', 'inasistencia' => 'inasistencia'];

        return redirect()->route('citas.gestion')->with('status', 'Cita registrada como '.$labels[$cita->estado].' correctamente.');
    }
}
