<?php

namespace App\Services\Citas;

use App\Models\CitaMedica;
use App\Models\HorarioAtencion;
use App\Models\IndisponibilidadMedico;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AppointmentAvailability
{
    /** @return Collection<int, array{horario_id:int, medico_id:int, medico:string, inicio:string, fin:string}> */
    public function slots(int $specialtyId, string $date, ?int $doctorId = null, ?int $patientId = null): Collection
    {
        $day = CarbonImmutable::createFromFormat('!Y-m-d', $date, config('app.timezone'));
        if ($day->isBefore(CarbonImmutable::today()) || $day->isAfter(CarbonImmutable::today()->addDays(config('citas.dias_anticipacion')))) {
            throw ValidationException::withMessages(['fecha' => 'Selecciona una fecha desde hoy hasta los próximos '.config('citas.dias_anticipacion').' días.']);
        }
        $schedules = HorarioAtencion::query()->with('medico')->where('activo', true)
            ->where('dia_semana', $day->isoWeekday())->whereDate('vigente_desde', '<=', $date)
            ->where(fn ($query) => $query->whereNull('vigente_hasta')->orWhereDate('vigente_hasta', '>=', $date))
            ->whereHas('medico', fn ($query) => $query->where('activo', true)->whereHas('especialidades', fn ($query) => $query->where('especialidades.id', $specialtyId)->where('especialidades.activo', true)->where('medico_especialidad.activo', true)))
            ->when($doctorId, fn ($query) => $query->where('medico_id', $doctorId))->get();
        $doctorIds = $schedules->pluck('medico_id');
        $blocks = IndisponibilidadMedico::query()->where('activo', true)->whereIn('medico_id', $doctorIds)->where('inicio', '<', $day->addDay())->where('fin', '>', $day)->get();
        $appointments = CitaMedica::query()->whereNotIn('estado', CitaMedica::RELEASED_STATES)
            ->where(fn ($query) => $query->whereIn('medico_id', $doctorIds)->when($patientId, fn ($query) => $query->orWhere('paciente_id', $patientId)))
            ->where('inicio', '<', $day->addDay())->where('fin', '>', $day)->get();
        $slots = collect();
        foreach ($schedules as $schedule) {
            $end = $day->setTimeFromTimeString($schedule->hora_fin);
            for ($start = $day->setTimeFromTimeString($schedule->hora_inicio); $start->addMinutes($schedule->duracion_turno_min)->lte($end); $start = $finish) {
                $finish = $start->addMinutes($schedule->duracion_turno_min);
                if ($start->lte(CarbonImmutable::now())) {
                    continue;
                }
                if ($blocks->contains(fn ($block) => $block->medico_id === $schedule->medico_id && $block->inicio->lt($finish) && $block->fin->gt($start))) {
                    continue;
                }
                if ($appointments->contains(fn ($appointment) => ($appointment->medico_id === $schedule->medico_id || ($patientId && $appointment->paciente_id === $patientId)) && $appointment->inicio->lt($finish) && $appointment->fin->gt($start))) {
                    continue;
                }
                $slots->push(['horario_id' => $schedule->id, 'medico_id' => $schedule->medico_id, 'medico' => $schedule->medico->nombres.' '.$schedule->medico->apellidos, 'inicio' => $start->format('Y-m-d H:i:s'), 'fin' => $finish->format('Y-m-d H:i:s')]);
            }
        }

        return $slots->sortBy(fn (array $slot) => $slot['inicio'].'-'.$slot['medico'])->values();
    }
}
