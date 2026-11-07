<?php

namespace App\Http\Controllers\Citas;

use App\Http\Controllers\Controller;
use App\Models\CitaMedica;
use App\Services\Citas\TurnoAtencionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TurnoController extends Controller
{
    public function index(Request $request): View
    {
        $actor = $request->user();
        $manager = $actor->tienePermiso('citas.gestionar');
        $doctor = ! $manager && $actor->tienePermiso('agenda.propia');
        $patient = ! $manager && ! $doctor && $actor->tienePermiso('citas.propias') && $actor->cumpleVerificacionParaAcceso();
        abort_unless($manager || $doctor || $patient, 403);
        $query = DB::table('llegadas_citas as l')->join('citas_medicas as c', 'c.id', '=', 'l.cita_id')
            ->join('pacientes as p', 'p.id', '=', 'c.paciente_id')->join('medicos as m', 'm.id', '=', 'c.medico_id')
            ->join('especialidades as e', 'e.id', '=', 'c.especialidad_id')
            ->whereColumn('c.inicio', 'l.inicio_programado')->whereColumn('c.medico_id', 'l.medico_id')->whereDate('c.inicio', now('America/La_Paz')->toDateString())
            ->whereIn('c.estado', ['programada', 'confirmada', 'en_curso'])
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('pagos_consulta as pc')->whereColumn('pc.cita_id', 'c.id')->where('pc.estado', '!=', 'pagado'));
        if ($doctor) {
            $query->where('m.usuario_id', $actor->id);
        } elseif ($patient) {
            $query->where('p.usuario_id', $actor->id)->where('p.activo', true);
        }
        $items = $query->select('l.id', 'l.llegada_en', 'c.id as cita_id', 'c.codigo', 'c.inicio', 'c.estado', 'c.medico_id', 'p.nombres', 'p.apellidos', 'm.nombres as medico_nombre', 'm.apellidos as medico_apellido', 'e.nombre as especialidad')->orderBy('l.llegada_en')->orderBy('l.id')->get();
        if ($patient) {
            foreach ($items as $item) {
                $item->antes = DB::table('llegadas_citas as l')->join('citas_medicas as c', 'c.id', '=', 'l.cita_id')
                    ->where('c.medico_id', $item->medico_id)->whereDate('c.inicio', now('America/La_Paz')->toDateString())
                    ->whereColumn('c.inicio', 'l.inicio_programado')->whereColumn('c.medico_id', 'l.medico_id')->whereIn('c.estado', ['programada', 'confirmada', 'en_curso'])
                    ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('pagos_consulta as pc')->whereColumn('pc.cita_id', 'c.id')->where('pc.estado', '!=', 'pagado'))
                    ->where(fn ($q) => $q->where('l.llegada_en', '<', $item->llegada_en)->orWhere(fn ($q) => $q->where('l.llegada_en', $item->llegada_en)->where('l.id', '<', $item->id)))->count();
            }
        }

        return view('citas.turnos', ['turnos' => $items, 'patient' => $patient, 'manager' => $manager]);
    }

    public function store(Request $request, CitaMedica $cita, TurnoAtencionService $service): RedirectResponse
    {
        $data = $request->validate(['version' => ['required', 'string', 'size:64']]);
        $service->registrarLlegada($request->user(), $cita->id, $data['version']);

        return redirect()->route('turnos.index')->with('status', 'Llegada registrada.');
    }
}
