<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Algunas categorías de incidencia (Equipos TI, Red y Conectividad,
// Suministros, Servicios Básicos) no calzan con ninguna palabra clave de
// las instituciones específicas (policía, bomberos, salud, eléctrica,
// agua, obras públicas, medio ambiente), así que quedaban con
// id_rol_responsable = NULL. Eso significa que NINGÚN rol institucional
// las veía — ni siquiera el Técnico. Esta migración les asigna el rol
// Técnico como responsable general, para que todas las categorías tengan
// a alguien a cargo.
return new class extends Migration
{
    public function up(): void
    {
        // 1) Las categorías que no calzaban con ninguna institución específica
        //    (Equipos TI, Red y Conectividad, Suministros, Servicios Básicos)
        //    quedan a cargo del rol Técnico.
        DB::statement("
            UPDATE tipos_incidencia t
            JOIN roles r ON r.slug = 'tecnico'
            SET t.id_rol_responsable = r.id_rol
            WHERE t.id_rol_responsable IS NULL
        ");

        // 2) Retroactivo: las incidencias que ya existían (por ejemplo, las
        //    de datos de ejemplo) nunca pasaron por el registro normal, así
        //    que nunca se les creó la asignación institucional. Esto hace
        //    ahora, para cada incidencia aprobada sin ningún "responsable"
        //    asignado todavía, lo mismo que ya hace el sistema al crear una
        //    incidencia nueva: asignarla a los usuarios institucionales
        //    (o técnicos) cuyo rol corresponda al tipo de esa incidencia.
        DB::statement("
            INSERT INTO incidencia_asignaciones (id_incidencia, id_usuario, rol_asignacion, fecha_asignacion)
            SELECT i.id_incidencia, u.id_usuario, 'responsable', NOW()
            FROM incidencias i
            JOIN tipos_incidencia t ON t.id_tipo = i.id_tipo
            JOIN usuarios u ON u.id_rol = t.id_rol_responsable
            WHERE i.estado_aprobacion = 'aprobada'
              AND t.id_rol_responsable IS NOT NULL
              AND u.activo = 1
              AND NOT EXISTS (
                  SELECT 1 FROM incidencia_asignaciones ia
                  WHERE ia.id_incidencia = i.id_incidencia AND ia.id_usuario = u.id_usuario
              )
        ");
    }

    public function down(): void
    {
        // No se elimina: dejar categorías o asignaciones huérfanas rompería
        // el panel institucional para esos casos.
    }
};
