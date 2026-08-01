<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Antes, cada vez que se reasignaba una incidencia a otro técnico, el
// anterior se quedaba pegado en vez de reemplazarse (bug ya corregido en
// asignarTecnico()). Esto limpia lo que ya quedó acumulado: para cada
// incidencia, deja solo el responsable asignado más recientemente y borra
// los demás (los de rol "apoyo" no se tocan).
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            DELETE ia FROM incidencia_asignaciones ia
            JOIN (
                SELECT id_incidencia, MAX(id_asignacion) AS ultimo
                FROM incidencia_asignaciones
                WHERE rol_asignacion = 'responsable'
                GROUP BY id_incidencia
            ) mantener ON mantener.id_incidencia = ia.id_incidencia
            WHERE ia.rol_asignacion = 'responsable'
              AND ia.id_asignacion <> mantener.ultimo
        ");
    }

    public function down(): void
    {
        // No se revierte: no queremos recuperar las asignaciones duplicadas.
    }
};
