<?php

namespace App\Http\Controllers\Recordatorios;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recordatorios\SaveReminderPreferencesRequest;
use App\Services\Recordatorios\ReminderManagement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PreferenciasController extends Controller
{
    public function __construct(private ReminderManagement $reminders) {}

    public function edit(Request $request): View
    {
        $patient = $this->reminders->patient($request->user());
        $request->validate(['page' => ['nullable', 'integer', 'min:1']]);
        $preferences = $this->reminders->preferences($patient->id);
        $history = DB::table('recordatorios_cita as r')->join('citas_medicas as c', 'c.id', '=', 'r.cita_id')
            ->where('c.paciente_id', $patient->id)->select(['r.id', 'r.estado', 'r.fecha_programada', 'r.fecha_envio', 'r.inicio_cita', 'c.codigo'])
            ->orderByDesc('r.id')->paginate(10);
        $resultHistory = DB::table('avisos_resultados')->where('paciente_id', $patient->id)
            ->where('usuario_id', $request->user()->id)->orderByDesc('id')->limit(20)->get();

        return view('recordatorios.edit', [
            'preferences' => $preferences, 'version' => $this->reminders->version($preferences),
            'details' => $this->reminders->details($preferences), 'history' => $history, 'resultHistory' => $resultHistory,
        ]);
    }

    public function update(SaveReminderPreferencesRequest $request): RedirectResponse
    {
        $this->reminders->save($request->user(), $request->validated());

        return redirect()->route('recordatorios.edit')->with('status', 'Tus preferencias de recordatorios se guardaron.');
    }
}
