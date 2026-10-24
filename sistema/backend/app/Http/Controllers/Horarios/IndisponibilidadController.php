<?php

namespace App\Http\Controllers\Horarios;

use App\Http\Controllers\Controller;
use App\Http\Requests\Horarios\ChangeScheduleStateRequest;
use App\Http\Requests\Horarios\SaveUnavailabilityRequest;
use App\Models\IndisponibilidadMedico;
use App\Models\Medico;
use App\Services\Horarios\ScheduleManagement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class IndisponibilidadController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['medico_id' => ['nullable', 'integer', Rule::exists('medicos', 'id')], 'estado' => ['nullable', Rule::in(['activo', 'inactivo'])]]);
        $query = IndisponibilidadMedico::query()->with('medico');
        if (! empty($filters['medico_id'])) {
            $query->where('medico_id', $filters['medico_id']);
        }
        if (! empty($filters['estado'])) {
            $query->where('activo', $filters['estado'] === 'activo');
        }

        return view('horarios.bloqueos', ['bloqueos' => $query->orderByDesc('inicio')->orderByDesc('id')->paginate(15)->withQueryString(), 'medicos' => Medico::query()->orderBy('apellidos')->get()]);
    }

    public function create(): View
    {
        return $this->form(new IndisponibilidadMedico);
    }

    public function edit(IndisponibilidadMedico $bloqueo): View
    {
        return $this->form($bloqueo);
    }

    private function form(IndisponibilidadMedico $bloqueo): View
    {
        return view('horarios.form', ['registro' => $bloqueo, 'tipo' => 'bloqueo', 'medicos' => Medico::query()->where(fn ($query) => $query->where('activo', true)->orWhere('id', $bloqueo->medico_id))->orderBy('apellidos')->get()]);
    }

    public function store(SaveUnavailabilityRequest $request, ScheduleManagement $management): RedirectResponse
    {
        $management->saveBlock($request->user(), $request->validated());

        return redirect()->route('horarios.bloqueos.index')->with('status', 'Bloqueo registrado correctamente.');
    }

    public function update(SaveUnavailabilityRequest $request, IndisponibilidadMedico $bloqueo, ScheduleManagement $management): RedirectResponse
    {
        $management->saveBlock($request->user(), $request->validated(), $bloqueo->id);

        return redirect()->route('horarios.bloqueos.index')->with('status', 'Bloqueo actualizado correctamente.');
    }

    public function editState(IndisponibilidadMedico $bloqueo): View
    {
        return view('horarios.estado', ['registro' => $bloqueo, 'tipo' => 'bloqueo']);
    }

    public function updateState(ChangeScheduleStateRequest $request, IndisponibilidadMedico $bloqueo, ScheduleManagement $management): RedirectResponse
    {
        $management->changeBlockState($request->user(), $bloqueo->id, $request->validated());

        return redirect()->route('horarios.bloqueos.index')->with('status', 'Estado del bloqueo actualizado.');
    }
}
