<?php

namespace App\Http\Controllers\Especialidades;

use App\Http\Controllers\Controller;
use App\Http\Requests\Especialidades\ChangeSpecialtyStateRequest;
use App\Http\Requests\Especialidades\SaveSpecialtyRequest;
use App\Models\Especialidad;
use App\Services\Especialidades\SpecialtyManagement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EspecialidadController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'estado' => ['nullable', Rule::in(['activo', 'inactivo'])]]);
        $query = Especialidad::query();
        if (isset($filters['q']) && trim($filters['q']) !== '') {
            $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], Especialidad::normalizeName($filters['q'])).'%';
            $descriptionTerm = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], Str::squish($filters['q'])).'%';
            $query->where(fn ($query) => $query->where('nombre_normalizado', 'like', $term)
                ->orWhere('descripcion', 'ilike', $descriptionTerm));
        }
        if (! empty($filters['estado'])) {
            $query->where('activo', $filters['estado'] === 'activo');
        }
        $especialidades = $query->orderBy('nombre_normalizado')->orderBy('id')->paginate(15)->withQueryString();

        return view('especialidades.index', compact('especialidades'));
    }

    public function create(): View
    {
        return view('especialidades.form', ['especialidad' => new Especialidad]);
    }

    public function edit(Especialidad $especialidad): View
    {
        return view('especialidades.form', compact('especialidad'));
    }

    public function editState(Especialidad $especialidad): View
    {
        return view('especialidades.estado', compact('especialidad'));
    }

    public function store(SaveSpecialtyRequest $request, SpecialtyManagement $management): RedirectResponse
    {
        $management->save($request->user(), $request->validated());

        return redirect()->route('especialidades.index')->with('status', 'Especialidad registrada correctamente.');
    }

    public function update(SaveSpecialtyRequest $request, Especialidad $especialidad, SpecialtyManagement $management): RedirectResponse
    {
        $management->save($request->user(), $request->validated(), $especialidad->id);

        return redirect()->route('especialidades.index')->with('status', 'Especialidad actualizada correctamente.');
    }

    public function updateState(ChangeSpecialtyStateRequest $request, Especialidad $especialidad, SpecialtyManagement $management): RedirectResponse
    {
        $especialidad = $management->changeState($request->user(), $especialidad->id, $request->validated());

        return redirect()->route('especialidades.index')->with('status', $especialidad->activo ? 'Especialidad activada correctamente.' : 'Especialidad desactivada correctamente.');
    }
}
