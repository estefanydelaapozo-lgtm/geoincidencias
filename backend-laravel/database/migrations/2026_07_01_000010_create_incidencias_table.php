<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidencias', function (Blueprint $table) {
            $table->id('id_incidencia');
            $table->string('titulo', 200);
            $table->text('descripcion')->nullable();
            $table->enum('prioridad', ['Baja', 'Media', 'Alta']);
            $table->foreignId('id_tipo')->constrained('tipos_incidencia', 'id_tipo');
            $table->foreignId('id_subtipo')->nullable()->constrained('subtipos_incidencia', 'id_subtipo');
            $table->foreignId('id_estado_actual')->constrained('estados', 'id_estado');
            $table->enum('estado_aprobacion', ['pendiente_revision', 'aprobada', 'rechazada'])->default('pendiente_revision');
            $table->foreignId('id_admin_revisor')->nullable()->constrained('usuarios', 'id_usuario');
            $table->timestamp('fecha_revision')->nullable();
            $table->text('motivo_rechazo')->nullable();
            $table->foreignId('id_zona')->constrained('zonas', 'id_zona');
            $table->decimal('latitud', 10, 6)->nullable();
            $table->decimal('longitud', 10, 6)->nullable();
            $table->string('direccion_texto', 255)->nullable();
            $table->date('fecha_ocurrencia');
            $table->time('hora_ocurrencia')->nullable();
            $table->timestamp('fecha_resolucion')->nullable();
            $table->decimal('tiempo_resolucion_horas', 10, 2)->nullable();
            $table->string('reportante_nombre', 100)->nullable();
            $table->string('reportante_contacto', 20)->nullable();
            $table->foreignId('id_usuario_creador')->constrained('usuarios', 'id_usuario');
            $table->timestamp('fecha_registro')->useCurrent();
            $table->timestamp('fecha_actualizacion')->nullable();
            $table->index(['estado_aprobacion', 'id_estado_actual', 'fecha_registro'], 'idx_incidencias_flujo');
            $table->index(['id_tipo', 'id_zona', 'prioridad'], 'idx_incidencias_filtros');
            $table->index('fecha_ocurrencia', 'idx_incidencias_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidencias');
    }
};
