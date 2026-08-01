<?php

namespace App\Services;

use App\Models\Incidencia;
use App\Models\IncidenciaAprobacionHistorial;
use App\Models\Notificacion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AprobacionAutomaticaService
{
    /**
     * Aprueba automáticamente las incidencias que llevan pendientes de revisión
     * más de 24 horas sin que un Administrador o Supervisor las haya
     * eliminado, aprobado o rechazado.
     *
     * @return int cantidad de incidencias aprobadas automáticamente
     */
    public static function procesar(): int
    {
        $vencidas = Incidencia::query()
            ->where('estado_aprobacion', 'pendiente_revision')
            ->whereNotNull('fecha_limite_accion')
            ->where('fecha_limite_accion', '<=', now())
            ->get();

        if ($vencidas->isEmpty()) {
            return 0;
        }

        $total = 0;
        foreach ($vencidas as $incidencia) {
            DB::transaction(function () use ($incidencia) {
                $motivo = 'Aprobación automática: transcurrieron 24 horas sin acción del Administrador o Supervisor.';
                $incidencia->update([
                    'estado_aprobacion' => 'aprobada',
                    'fecha_aprobacion' => now(),
                    'aprobacion_automatica' => true,
                    'motivo_aprobacion' => $motivo,
                ]);
                IncidenciaAprobacionHistorial::create([
                    'id_incidencia' => $incidencia->id_incidencia,
                    'id_usuario' => null,
                    'accion' => 'aprobada_automatica',
                    'motivo' => $motivo,
                ]);
                if ($incidencia->id_usuario_creador) {
                    Notificacion::create([
                        'id_usuario' => $incidencia->id_usuario_creador,
                        'id_incidencia' => $incidencia->id_incidencia,
                        'titulo' => 'Incidencia aprobada automáticamente',
                        'mensaje' => "\"{$incidencia->titulo}\" fue aprobada automáticamente al no recibir acción en 24 horas.",
                    ]);
                }
            });
            $total++;
        }

        Cache::forget('incidencias.mapa');

        return $total;
    }

    /**
     * Ejecuta el procesamiento como máximo una vez cada cierto intervalo,
     * para poder invocarlo de forma económica desde peticiones normales
     * (index, pendientes, detalle) sin depender de un cron configurado.
     */
    public static function procesarConLimite(int $segundos = 30): void
    {
        if (Cache::add('lock_auto_aprobacion_incidencias', 1, $segundos)) {
            self::procesar();
        }
    }
}
