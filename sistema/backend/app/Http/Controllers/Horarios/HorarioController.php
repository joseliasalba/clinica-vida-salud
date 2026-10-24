<?php

namespace App\Http\Controllers\Horarios;

use App\Http\Controllers\Controller;
use App\Http\Requests\Horarios\ChangeScheduleStateRequest;
use App\Http\Requests\Horarios\SaveScheduleRequest;
use App\Models\HorarioAtencion;
use App\Models\Medico;
use App\Services\Horarios\ScheduleManagement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HorarioController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['medico_id' => ['nullable', 'integer', Rule::exists('medicos', 'id')], 'dia_semana' => ['nullable', 'integer', 'between:1,7'], 'estado' => ['nullable', Rule::in(['activo', 'inactivo'])]]);
        $query = HorarioAtencion::query()->with('medico');
        foreach (['medico_id', 'dia_semana'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }
        if (! empty($filters['estado'])) {
            $query->where('activo', $filters['estado'] === 'activo');
        }

        return view('horarios.index', ['horarios' => $query->orderBy('medico_id')->orderBy('dia_semana')->orderBy('hora_inicio')->paginate(15)->withQueryString(), 'medicos' => Medico::query()->orderBy('apellidos')->get()]);
    }

    public function create(): View
    {
        return $this->form(new HorarioAtencion);
    }

    public function edit(HorarioAtencion $horario): View
    {
        return $this->form($horario);
    }

    private function form(HorarioAtencion $horario): View
    {
        return view('horarios.form', ['registro' => $horario, 'tipo' => 'horario', 'medicos' => Medico::query()->where(fn ($query) => $query->where('activo', true)->orWhere('id', $horario->medico_id))->orderBy('apellidos')->get()]);
    }

    public function store(SaveScheduleRequest $request, ScheduleManagement $management): RedirectResponse
    {
        $management->saveSchedule($request->user(), $request->validated());

        return redirect()->route('horarios.index')->with('status', 'Horario registrado correctamente.');
    }

    public function update(SaveScheduleRequest $request, HorarioAtencion $horario, ScheduleManagement $management): RedirectResponse
    {
        $management->saveSchedule($request->user(), $request->validated(), $horario->id);

        return redirect()->route('horarios.index')->with('status', 'Horario actualizado correctamente.');
    }

    public function editState(HorarioAtencion $horario): View
    {
        return view('horarios.estado', ['registro' => $horario, 'tipo' => 'horario']);
    }

    public function updateState(ChangeScheduleStateRequest $request, HorarioAtencion $horario, ScheduleManagement $management): RedirectResponse
    {
        $management->changeScheduleState($request->user(), $horario->id, $request->validated());

        return redirect()->route('horarios.index')->with('status', 'Estado del horario actualizado.');
    }
}
