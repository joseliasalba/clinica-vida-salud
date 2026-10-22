<?php

namespace App\Http\Controllers\Pacientes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pacientes\SavePatientRequest;
use App\Models\Paciente;
use App\Services\Pacientes\PatientRegistration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PacienteController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'estado' => ['nullable', Rule::in(['activo', 'inactivo'])]]);
        $query = Paciente::query()->select(['id', 'codigo', 'ci', 'nombres', 'apellidos', 'telefono', 'activo']);
        if (isset($filters['q']) && trim($filters['q']) !== '') {
            $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim($filters['q'])).'%';
            $query->where(fn ($query) => $query->where('codigo', 'ilike', $term)->orWhere('ci', 'ilike', $term)
                ->orWhereRaw("concat_ws(' ', nombres, apellidos) ilike ?", [$term]));
        }
        if (! empty($filters['estado'])) {
            $query->where('activo', $filters['estado'] === 'activo');
        }
        $pacientes = $query->orderBy('apellidos')->orderBy('nombres')->orderBy('id')->paginate(15)->withQueryString();

        return view('pacientes.index', compact('pacientes'));
    }

    public function create(): View
    {
        return view('pacientes.form', ['paciente' => new Paciente]);
    }

    public function show(Paciente $paciente): View
    {
        return view('pacientes.show', compact('paciente'));
    }

    public function edit(Paciente $paciente): View
    {
        return view('pacientes.form', compact('paciente'));
    }

    public function store(SavePatientRequest $request, PatientRegistration $registration): RedirectResponse
    {
        $paciente = $registration->save($request->user(), $request->validated());

        return redirect()->route('pacientes.show', $paciente)->with('status', 'Paciente registrado correctamente.');
    }

    public function update(SavePatientRequest $request, Paciente $paciente, PatientRegistration $registration): RedirectResponse
    {
        $registration->save($request->user(), $request->validated(), $paciente->id);

        return redirect()->route('pacientes.show', $paciente)->with('status', 'Ficha del paciente actualizada correctamente.');
    }
}
