<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Campos adicionales de aprobación en la propia incidencia ──
        Schema::table('incidencias', function (Blueprint $table) {
            if (!Schema::hasColumn('incidencias', 'motivo_aprobacion')) {
                $table->text('motivo_aprobacion')->nullable()->after('motivo_rechazo');
            }
            if (!Schema::hasColumn('incidencias', 'fecha_aprobacion')) {
                $table->timestamp('fecha_aprobacion')->nullable()->after('fecha_revision');
            }
            if (!Schema::hasColumn('incidencias', 'fecha_rechazo')) {
                $table->timestamp('fecha_rechazo')->nullable()->after('fecha_aprobacion');
            }
            if (!Schema::hasColumn('incidencias', 'aprobacion_automatica')) {
                $table->boolean('aprobacion_automatica')->default(false)->after('fecha_rechazo');
            }
            if (!Schema::hasColumn('incidencias', 'fecha_limite_accion')) {
                // Plazo (fecha_registro + 24h) dentro del cual admin/supervisor pueden
                // eliminar o rechazar la incidencia. Vencido este plazo sin acción,
                // el sistema la aprueba automáticamente.
                $table->timestamp('fecha_limite_accion')->nullable()->after('aprobacion_automatica');
            }
        });

        // Completar el plazo para incidencias ya existentes que no lo tengan.
        DB::table('incidencias')
            ->whereNull('fecha_limite_accion')
            ->update(['fecha_limite_accion' => DB::raw('DATE_ADD(fecha_registro, INTERVAL 24 HOUR)')]);

        // ── Historial de aprobación / rechazo (manual o automático) ──
        if (!Schema::hasTable('incidencia_aprobaciones_historial')) {
            Schema::create('incidencia_aprobaciones_historial', function (Blueprint $table) {
                $table->id('id_historial_aprobacion');
                $table->foreignId('id_incidencia')->constrained('incidencias', 'id_incidencia')->onDelete('cascade');
                $table->foreignId('id_usuario')->nullable()->constrained('usuarios', 'id_usuario');
                $table->enum('accion', ['aprobada', 'rechazada', 'aprobada_automatica']);
                $table->text('motivo')->nullable();
                $table->timestamp('fecha')->useCurrent();
                $table->index(['id_incidencia', 'fecha'], 'idx_aprobaciones_incidencia_fecha');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('incidencia_aprobaciones_historial');
        Schema::table('incidencias', function (Blueprint $table) {
            foreach (['motivo_aprobacion', 'fecha_aprobacion', 'fecha_rechazo', 'aprobacion_automatica', 'fecha_limite_accion'] as $col) {
                if (Schema::hasColumn('incidencias', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
