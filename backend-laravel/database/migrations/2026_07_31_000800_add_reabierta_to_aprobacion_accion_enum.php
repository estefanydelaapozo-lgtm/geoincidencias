<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Corrige un bug: la acción "reabrir" (agregada junto con los estados
// 'En verificación'/'Reabierta') intentaba guardar accion='reabierta' en
// incidencia_aprobaciones_historial, pero esa columna era un ENUM que solo
// aceptaba 'aprobada','rechazada','aprobada_automatica'. MySQL truncaba el
// valor y devolvía el warning 1265. Esta migración amplía el ENUM sin tocar
// los valores existentes.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('incidencia_aprobaciones_historial')) {
            DB::statement("ALTER TABLE incidencia_aprobaciones_historial MODIFY accion ENUM('aprobada','rechazada','aprobada_automatica','reabierta') NOT NULL");
        }
        // 'detalle' era VARCHAR(255); los mensajes de reabrir/solicitar revisión
        // incluyen el motivo completo (hasta 500 caracteres) y podían truncarse
        // o fallar en modo estricto. Se amplía a TEXT sin perder datos existentes.
        if (Schema::hasColumn('historial_actividad', 'detalle')) {
            DB::statement('ALTER TABLE historial_actividad MODIFY detalle TEXT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('incidencia_aprobaciones_historial')) {
            DB::statement("ALTER TABLE incidencia_aprobaciones_historial MODIFY accion ENUM('aprobada','rechazada','aprobada_automatica') NOT NULL");
        }
    }
};
