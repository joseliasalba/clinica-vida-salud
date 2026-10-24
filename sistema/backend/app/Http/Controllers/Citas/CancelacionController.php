<?php

namespace App\Http\Controllers\Citas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Citas\CancelarCitaRequest;
use App\Models\CitaMedica;
use App\Services\Citas\CancelacionService;
use App\Services\Citas\CitaWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CancelacionController extends Controller
{
    public function edit(Request $request, CitaMedica $cita, CitaWorkflow $workflow): View
    {
        $workflow->authorize($request->user(), $cita);
        abort_unless(in_array($cita->estado, ['programada', 'confirmada'], true), 409, 'Esta cita ya no se puede cancelar.');
        $cita->load(['paciente', 'medico', 'especialidad']);

        return view('citas.cancelar', compact('cita'));
    }

    public function update(CancelarCitaRequest $request, CitaMedica $cita, CancelacionService $service): RedirectResponse
    {
        $service->cancelar($request->user(), $cita->id, $request->validated());

        return redirect()->route('citas.gestion')->with('status', 'Cita cancelada correctamente. El horario quedó disponible y se conservó el historial.');
    }
}
