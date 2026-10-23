<?php

namespace App\Services\Citas;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class ReprogramacionDisponibilidadService
{
    /**
     * Los turnos se recalculan al guardar; la consulta previa nunca reserva un espacio.
     *
     * @return array<int, array{horario_id: int, inicio: string, fin: string, duracion_minutos: int}>
     */
    public function turnos(int $medicoId, int $especialidadId, string $fecha, ?int $excluirCitaId = null): array
    {
        $dia = CarbonImmutable::createFromFormat('!Y-m-d', $fecha, 'America/La_Paz');
        $ahora = CarbonImmutable::now('America/La_Paz');
        if (! $dia || $dia->format('Y-m-d') !== $fecha || $dia->endOfDay()->lessThan($ahora)) {
            return [];
        }

        $habilitado = DB::table('medico_especialidad')
            ->join('medicos', 'medicos.id', '=', 'medico_especialidad.medico_id')
            ->join('especialidades', 'especialidades.id', '=', 'medico_especialidad.especialidad_id')
            ->where('medico_especialidad.medico_id', $medicoId)
            ->where('medico_especialidad.especialidad_id', $especialidadId)
            ->where('medico_especialidad.activo', true)
            ->where('medicos.activo', true)
            ->where('especialidades.activo', true)->exists();
        if (! $habilitado) {
            return [];
        }

        $horarios = DB::table('horarios_atencion')->where('medico_id', $medicoId)
            ->where('activo', true)->where('dia_semana', $dia->dayOfWeekIso)
            ->whereDate('vigente_desde', '<=', $fecha)
            ->where(fn ($query) => $query->whereNull('vigente_hasta')->orWhereDate('vigente_hasta', '>=', $fecha))
            ->orderBy('hora_inicio')->orderBy('id')->get();
        $bloqueos = DB::table('indisponibilidades_medico')->where('medico_id', $medicoId)->where('activo', true)
            ->where('inicio', '<', $dia->addDay()->format('Y-m-d H:i:s'))->where('fin', '>', $dia->format('Y-m-d H:i:s'))->get(['inicio', 'fin']);
        $citas = DB::table('citas_medicas')->where('medico_id', $medicoId)->where('estado', '<>', 'cancelada')
            ->when($excluirCitaId !== null, fn ($query) => $query->where('id', '<>', $excluirCitaId))
            ->where('inicio', '<', $dia->addDay()->format('Y-m-d H:i:s'))->where('fin', '>', $dia->format('Y-m-d H:i:s'))->get(['inicio', 'fin']);
        $ocupados = $bloqueos->concat($citas);
        $turnos = [];
        foreach ($horarios as $horario) {
            $duracion = (int) $horario->duracion_turno_min;
            if ($duracion <= 0) {
                continue;
            }
            $inicio = CarbonImmutable::parse($fecha.' '.$horario->hora_inicio, 'America/La_Paz');
            $limite = CarbonImmutable::parse($fecha.' '.$horario->hora_fin, 'America/La_Paz');
            while ($inicio->addMinutes($duracion)->lessThanOrEqualTo($limite)) {
                $fin = $inicio->addMinutes($duracion);
                $inicioSql = $inicio->format('Y-m-d H:i:s');
                $finSql = $fin->format('Y-m-d H:i:s');
                if ($inicio->greaterThan($ahora) && ! $ocupados->contains(fn ($ocupado) => $ocupado->inicio < $finSql && $ocupado->fin > $inicioSql)) {
                    $turnos[$inicioSql.'|'.$finSql] ??= ['horario_id' => (int) $horario->id, 'inicio' => $inicioSql, 'fin' => $finSql, 'duracion_minutos' => $duracion];
                }
                $inicio = $fin;
            }
        }

        ksort($turnos);

        return array_values($turnos);
    }
}
