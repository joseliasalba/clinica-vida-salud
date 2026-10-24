<?php

namespace App\Http\Controllers\Medicos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Medicos\ChangeDoctorStateRequest;
use App\Http\Requests\Medicos\SaveDoctorRequest;
use App\Models\Especialidad;
use App\Models\Medico;
use App\Services\Medicos\DoctorManagement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MedicoController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'estado' => ['nullable', Rule::in(['activo', 'inactivo'])], 'especialidad' => ['nullable', 'integer', Rule::exists('especialidades', 'id')]]);
        $query = Medico::query()->with(['especialidades', 'cuenta:id,nombre_usuario']);
        if (isset($filters['q']) && trim($filters['q']) !== '') {
            $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim($filters['q'])).'%';
            $query->where(fn ($query) => $query->where('codigo', 'ilike', $term)->orWhere('registro_profesional', 'ilike', $term)->orWhereRaw("concat_ws(' ', nombres, apellidos) ilike ?", [$term]));
        }
        if (! empty($filters['estado'])) {
            $query->where('activo', $filters['estado'] === 'activo');
        }
        if (! empty($filters['especialidad'])) {
            $query->whereHas('especialidades', fn ($specialties) => $specialties->where('especialidades.id', $filters['especialidad'])->where('medico_especialidad.activo', true));
        }

        return view('medicos.index', ['medicos' => $query->orderBy('apellidos')->orderBy('nombres')->orderBy('id')->paginate(15)->withQueryString(), 'especialidades' => Especialidad::query()->orderBy('nombre')->get()]);
    }

    public function create(DoctorManagement $management): View
    {
        return $this->form(new Medico, $management);
    }

    public function edit(Medico $medico, DoctorManagement $management): View
    {
        return $this->form($medico, $management);
    }

    private function form(Medico $medico, DoctorManagement $management): View
    {
        $accounts = $management->eligibleAccounts()->whereNotIn('id', Medico::query()->whereNotNull('usuario_id')->when($medico->exists, fn ($query) => $query->where('id', '<>', $medico->id))->select('usuario_id'))->orderBy('nombre_usuario')->get();
        if ($medico->usuario_id && ! $accounts->contains('id', $medico->usuario_id) && $medico->cuenta) {
            $accounts->push($medico->cuenta);
        }

        return view('medicos.form', ['medico' => $medico, 'especialidades' => Especialidad::query()->orderBy('nombre')->get(), 'cuentas' => $accounts, 'seleccionadas' => $medico->exists ? $medico->especialidades()->wherePivot('activo', true)->pluck('especialidades.id')->all() : []]);
    }

    public function store(SaveDoctorRequest $request, DoctorManagement $management): RedirectResponse
    {
        $management->save($request->user(), $request->validated());

        return redirect()->route('medicos.index')->with('status', 'Médico registrado correctamente.');
    }

    public function update(SaveDoctorRequest $request, Medico $medico, DoctorManagement $management): RedirectResponse
    {
        $management->save($request->user(), $request->validated(), $medico->id);

        return redirect()->route('medicos.index')->with('status', 'Ficha médica administrativa actualizada correctamente.');
    }

    public function editState(Medico $medico): View
    {
        return view('medicos.estado', ['medico' => $medico, 'citasPendientes' => DB::table('citas_medicas')->where('medico_id', $medico->id)->where('inicio', '>', now())->whereNotIn('estado', ['cancelada', 'inasistencia', 'completada'])->count()]);
    }

    public function updateState(ChangeDoctorStateRequest $request, Medico $medico, DoctorManagement $management): RedirectResponse
    {
        $doctor = $management->changeState($request->user(), $medico->id, $request->validated());

        return redirect()->route('medicos.index')->with('status', $doctor->activo ? 'Médico activado correctamente.' : 'Médico desactivado. Sus horarios, citas y cuenta se conservan.');
    }
}
