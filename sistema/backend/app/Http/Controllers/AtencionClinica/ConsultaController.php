<?php

namespace App\Http\Controllers\AtencionClinica;

use App\Http\Controllers\Controller;
use App\Models\CitaMedica;
use App\Services\Estudios\StudyManagement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ConsultaController extends Controller
{
    public function __construct(private StudyManagement $studies) {}

    public function index(Request $request): View
    {
        $citas = $this->appointments($request)
            ->select(['id', 'codigo', 'inicio', 'estado', 'paciente_id'])
            ->with('paciente:id,nombres,apellidos')->orderByDesc('inicio')->orderByDesc('id')->paginate(20);

        return view('consultas.index', compact('citas'));
    }

    public function show(Request $request, int $cita): View
    {
        $appointment = $this->appointments($request)->with('paciente:id,nombres,apellidos')->findOrFail($cita);
        $consulta = DB::table('consultas_medicas')->where('cita_id', $appointment->id)->first();
        $tratamientos = $consulta ? DB::table('tratamientos')->where('consulta_id', $consulta->id)->orderBy('id')->get() : collect();

        return view('consultas.show', compact('appointment', 'consulta', 'tratamientos'));
    }

    public function store(Request $request, int $cita): RedirectResponse
    {
        $data = $request->validate([
            'motivo_consulta' => ['nullable', 'string', 'max:2000'],
            'sintomas' => ['nullable', 'string', 'max:4000'],
            'evaluacion' => ['required', 'string', 'max:4000'],
            'observaciones' => ['nullable', 'string', 'max:4000'],
            'peso_kg' => ['nullable', 'numeric', 'gt:0', 'max:999.99', 'decimal:0,2'],
            'talla_cm' => ['nullable', 'numeric', 'gt:0', 'max:300', 'decimal:0,2'],
            'presion_sistolica' => ['nullable', 'integer', 'between:1,300'],
            'presion_diastolica' => ['nullable', 'integer', 'between:1,300'],
            'temperatura_c' => ['nullable', 'numeric', 'between:1,60', 'decimal:0,1'],
            'escala_dolor' => ['nullable', 'integer', 'between:0,10'],
            'indicaciones' => ['nullable', 'required_with:fecha_inicio,fecha_fin', 'string', 'max:4000'],
            'fecha_inicio' => ['nullable', 'required_with:fecha_fin', 'date_format:Y-m-d'],
            'fecha_fin' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:fecha_inicio'],
        ]);

        DB::transaction(function () use ($request, $cita, $data): void {
            $appointment = $this->appointments($request)->lockForUpdate()->findOrFail($cita);
            if (DB::table('consultas_medicas')->where('cita_id', $appointment->id)->exists()) {
                throw ValidationException::withMessages(['evaluacion' => 'Esta cita ya tiene una consulta registrada.']);
            }

            DB::table('pacientes')->where('id', $appointment->paciente_id)->lockForUpdate()->first();
            $history = DB::table('historias_clinicas')->where('paciente_id', $appointment->paciente_id)->first();
            $historyId = $history?->id ?? DB::table('historias_clinicas')->insertGetId([
                'paciente_id' => $appointment->paciente_id,
                'nro_expediente' => 'HC-'.$appointment->paciente_id,
            ]);
            $consultaId = DB::table('consultas_medicas')->insertGetId([
                ...Arr::except($data, ['indicaciones', 'fecha_inicio', 'fecha_fin']),
                'historia_id' => $historyId,
                'cita_id' => $appointment->id,
                'medico_id' => $appointment->medico_id,
                'fecha_atencion' => now(),
            ]);
            if (isset($data['indicaciones'])) {
                DB::table('tratamientos')->insert([
                    'consulta_id' => $consultaId,
                    'indicaciones' => $data['indicaciones'],
                    'fecha_inicio' => $data['fecha_inicio'] ?? null,
                    'fecha_fin' => $data['fecha_fin'] ?? null,
                ]);
            }
        });

        return redirect()->route('consultas.show', $cita)->with('status', 'Consulta registrada.');
    }

    private function appointments(Request $request): Builder
    {
        return CitaMedica::where('medico_id', $this->studies->doctorId($request->user(), 'consultas.registrar'))
            ->whereIn('estado', ['en_curso', 'completada', 'atendida'])->where('inicio', '<=', now());
    }
}
