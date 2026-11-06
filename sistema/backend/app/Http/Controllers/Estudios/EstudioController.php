<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use App\Http\Requests\Estudios\SaveStudyResultRequest;
use App\Http\Requests\Estudios\StoreStudyRequest;
use App\Models\SolicitudEstudio;
use App\Services\Estudios\StudyManagement;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EstudioController extends Controller
{
    public function __construct(private StudyManagement $studies) {}

    public function index(Request $request): View
    {
        $actor = $request->user();
        $query = $this->studies->visible($actor);
        $filters = $request->validate([
            'estado' => ['nullable', Rule::in(array_keys(SolicitudEstudio::ESTADOS))],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        if (! empty($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }
        $doctor = $this->studies->isDoctor($actor);
        $query->with(['resultado' => function (HasOne $query) use ($doctor): void {
            if (! $doctor) {
                $query->where('estado_publicacion', 'publicado');
            }
        }]);
        $estudios = $query->orderByDesc('fecha_solicitud')->orderByDesc('id')->paginate(15)->withQueryString();
        $consultas = $this->studies->consultations()->whereIn('cm.id', $estudios->pluck('consulta_id'))->get()->keyBy('id');

        return view('estudios.index', compact('estudios', 'doctor', 'consultas'));
    }

    public function create(Request $request): View
    {
        $doctorId = $this->studies->doctorId($request->user(), 'estudios.solicitar');
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'consulta_id' => ['nullable', 'integer', 'min:1'],
        ]);
        $query = $this->studies->consultations()->where('cm.medico_id', $doctorId);
        $consultaSeleccionada = $filters['consulta_id'] ?? null;
        if ($consultaSeleccionada) {
            $query->where('cm.id', $consultaSeleccionada);
        }
        if (! empty($filters['q'])) {
            $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $filters['q']).'%';
            $query->where(fn ($query) => $query->where('p.codigo', 'ilike', $term)->orWhere('p.nombres', 'ilike', $term)->orWhere('p.apellidos', 'ilike', $term)->orWhere('c.codigo', 'ilike', $term));
        }
        $consultas = $query->orderByDesc('cm.fecha_atencion')->orderByDesc('cm.id')->paginate(15)->withQueryString();

        return view('estudios.create', compact('consultas', 'consultaSeleccionada'));
    }

    public function store(StoreStudyRequest $request): RedirectResponse
    {
        $study = $this->studies->request($request->user(), $request->validated());

        return redirect()->route('estudios.show', $study)->with('status', 'Solicitud de estudio registrada.');
    }

    public function show(Request $request, int $estudio): View
    {
        $study = $this->studies->findVisible($request->user(), $estudio);
        $doctor = $this->studies->isDoctor($request->user());
        $consulta = $this->studies->consultations()->where('cm.id', $study->consulta_id)->firstOrFail();

        return view('estudios.show', ['estudio' => $study, 'doctor' => $doctor, 'consulta' => $consulta]);
    }

    public function saveResult(SaveStudyResultRequest $request, int $estudio): RedirectResponse
    {
        $this->studies->saveResult($request->user(), $estudio, $request->validated(), $request->file('archivo'));

        return redirect()->route('estudios.show', $estudio)->with('status', 'Resultado actualizado.');
    }

    public function changeState(Request $request, int $estudio): RedirectResponse
    {
        $data = $request->validate([
            'version' => ['required', 'string', 'size:64'],
            'estado' => ['required', Rule::in(['en_proceso', 'cancelado'])],
            'motivo' => ['required_if:estado,cancelado', 'nullable', 'string', 'max:500'],
            'confirmacion' => ['accepted'],
        ]);
        $this->studies->changeState($request->user(), $estudio, $data);

        return redirect()->route('estudios.show', $estudio)->with('status', 'Estado del estudio actualizado.');
    }

    public function download(Request $request, int $estudio): StreamedResponse
    {
        $study = $this->studies->findVisible($request->user(), $estudio);
        $path = $study->resultado?->archivo_privado;
        abort_unless($path && Storage::disk('estudios')->exists($path), 404);

        return Storage::disk('estudios')->download($path, 'resultado-estudio-'.$study->id.'.pdf', [
            'Content-Type' => 'application/pdf', 'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
