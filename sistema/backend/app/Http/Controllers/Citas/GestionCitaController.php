<?php

namespace App\Http\Controllers\Citas;

use App\Http\Controllers\Controller;
use App\Models\CitaMedica;
use App\Services\Citas\CitaWorkflow;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GestionCitaController extends Controller
{
    public function index(Request $request): View
    {
        $actor = $request->user();
        $canManage = $actor->tienePermiso('citas.gestionar');
        abort_unless($canManage || $actor->tienePermiso('agenda.propia'), 403);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'], 'fecha' => ['nullable', 'date_format:Y-m-d'],
            'estado' => ['nullable', Rule::in(array_keys(CitaWorkflow::TRANSITIONS))],
        ]);
        $query = CitaMedica::query()->with(['paciente', 'medico', 'especialidad']);
        if (! $canManage) {
            $query->whereHas('medico', fn ($medico) => $medico->where('usuario_id', $actor->id));
        }
        if (isset($filters['q']) && trim($filters['q']) !== '') {
            $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim($filters['q'])).'%';
            $query->where(fn ($search) => $search->where('codigo', 'ilike', $term)
                ->orWhereHas('paciente', fn ($patient) => $patient->whereRaw("(nombres || ' ' || apellidos) ILIKE ?", [$term])));
        }
        if (! empty($filters['fecha'])) {
            $query->whereDate('inicio', $filters['fecha']);
        }
        if (! empty($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }
        $citas = $query->orderByDesc('inicio')->orderByDesc('id')->paginate(15)->withQueryString();

        return view('citas.gestion', compact('citas', 'canManage'));
    }
}
