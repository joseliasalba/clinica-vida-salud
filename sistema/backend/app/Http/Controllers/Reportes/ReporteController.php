<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Models\CitaMedica;
use App\Models\Especialidad;
use App\Models\Medico;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReporteController extends Controller
{
    public function index(Request $request): View
    {
        $states = ['programada', 'confirmada', 'pendiente_pago', 'en_curso', 'completada', 'atendida', 'cancelada', 'inasistencia'];
        $filters = $request->validate([
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('desde'), 'after_or_equal:desde')],
            'medico_id' => ['nullable', 'integer', 'exists:medicos,id'],
            'especialidad_id' => ['nullable', 'integer', 'exists:especialidades,id'],
            'estado' => ['nullable', Rule::in($states)],
        ]);

        $query = CitaMedica::query();
        foreach (['medico_id', 'especialidad_id', 'estado'] as $field) {
            if (isset($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }
        if (isset($filters['desde'])) {
            $query->where('inicio', '>=', $filters['desde'].' 00:00:00');
        }
        if (isset($filters['hasta'])) {
            $query->where('inicio', '<', Carbon::parse($filters['hasta'])->addDay()->startOfDay());
        }

        $totalPacientes = (clone $query)->distinct()->count('paciente_id');
        $citas = $query->select(['id', 'codigo', 'inicio', 'estado', 'paciente_id', 'medico_id', 'especialidad_id'])
            ->with(['paciente:id,codigo', 'medico:id,nombres,apellidos', 'especialidad:id,nombre'])
            ->orderByDesc('inicio')->orderByDesc('id')->paginate(20)->withQueryString();
        $medicos = Medico::orderBy('apellidos')->get(['id', 'nombres', 'apellidos']);
        $especialidades = Especialidad::orderBy('nombre')->get(['id', 'nombre']);
        $estados = collect($states)->mapWithKeys(fn (string $state): array => [
            $state => (new CitaMedica(['estado' => $state]))->estadoLabel(),
        ]);

        return view('reportes.index', compact('filters', 'citas', 'totalPacientes', 'medicos', 'especialidades', 'estados'));
    }
}
