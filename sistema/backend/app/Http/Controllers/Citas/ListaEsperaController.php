<?php

namespace App\Http\Controllers\Citas;

use App\Http\Controllers\Controller;
use App\Services\Citas\ListaEsperaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ListaEsperaController extends Controller
{
    public function index(Request $request): View
    {
        $actor = $request->user();
        $manager = $actor->tienePermiso('citas.gestionar');
        abort_unless($manager || ($actor->tienePermiso('citas.propias') && $actor->cumpleVerificacionParaAcceso()), 403);
        $filters = $request->validate(['fecha' => ['nullable', 'date_format:Y-m-d'], 'estado' => ['nullable', Rule::in(['esperando', 'contactado', 'finalizado', 'cancelado'])]]);
        $query = DB::table('lista_espera_citas as l')->join('pacientes as p', 'p.id', '=', 'l.paciente_id')
            ->join('especialidades as e', 'e.id', '=', 'l.especialidad_id')->leftJoin('medicos as m', 'm.id', '=', 'l.medico_id')
            ->select('l.*', 'p.nombres', 'p.apellidos', 'p.codigo', 'p.telefono', 'e.nombre as especialidad', 'm.nombres as medico_nombre', 'm.apellidos as medico_apellido');
        if (! $manager) {
            $query->where('p.usuario_id', $actor->id)->where('p.activo', true);
        }
        if (! empty($filters['fecha'])) {
            $query->where('l.fecha', $filters['fecha']);
        }
        if (! empty($filters['estado'])) {
            $query->where('l.estado', $filters['estado']);
        } else {
            $query->whereIn('l.estado', ['esperando', 'contactado'])->whereDate('l.fecha', '>=', now()->toDateString());
        }

        return view('citas.lista-espera', ['solicitudes' => $query->orderBy('l.created_at')->orderBy('l.id')->paginate(20)->withQueryString(), 'manager' => $manager]);
    }

    public function store(Request $request, ListaEsperaService $service): RedirectResponse
    {
        $data = $request->validate([
            'paciente_id' => ['nullable', 'integer'], 'especialidad_id' => ['required', 'integer'], 'medico_id' => ['nullable', 'integer'],
            'fecha' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:'.now()->addDays(config('citas.dias_anticipacion'))->toDateString()],
        ]);
        $service->inscribir($request->user(), $data);

        return redirect()->route('lista-espera.index')->with('status', 'Inscripción registrada. Recepción te contactará si puede ofrecerte un turno.');
    }

    public function update(Request $request, int $solicitud, ListaEsperaService $service): RedirectResponse
    {
        $data = $request->validate(['estado' => ['required', Rule::in(['esperando', 'contactado', 'finalizado', 'cancelado'])]]);
        $service->cambiarEstado($request->user(), $solicitud, $data['estado']);

        return redirect()->route('lista-espera.index')->with('status', 'Estado actualizado.');
    }
}
