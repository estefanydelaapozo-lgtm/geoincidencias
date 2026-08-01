<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Incidencia extends Model
{
    protected $table = 'incidencias';
    protected $primaryKey = 'id_incidencia';
    public $timestamps = false;

    protected $fillable = [
        'titulo', 'descripcion', 'prioridad',
        'id_tipo', 'id_subtipo', 'id_estado_actual',
        'estado_aprobacion', 'id_admin_revisor', 'fecha_revision', 'motivo_rechazo',
        'motivo_aprobacion', 'fecha_aprobacion', 'fecha_rechazo', 'aprobacion_automatica', 'fecha_limite_accion',
        'id_zona', 'latitud', 'longitud', 'direccion_texto', 'foto',
        'fecha_ocurrencia', 'hora_ocurrencia', 'fecha_resolucion', 'tiempo_resolucion_horas',
        'reportante_nombre', 'reportante_contacto', 'id_usuario_creador',
    ];

    protected $casts = [
        'latitud' => 'decimal:6',
        'longitud' => 'decimal:6',
        'fecha_ocurrencia' => 'date',
        'fecha_resolucion' => 'datetime',
        'fecha_revision' => 'datetime',
        'fecha_registro' => 'datetime',
        'fecha_actualizacion' => 'datetime',
        'fecha_aprobacion' => 'datetime',
        'fecha_rechazo' => 'datetime',
        'fecha_limite_accion' => 'datetime',
        'aprobacion_automatica' => 'boolean',
    ];

    // ── Relaciones ──
    public function tipo()
    {
        return $this->belongsTo(TipoIncidencia::class, 'id_tipo', 'id_tipo');
    }

    public function subtipo()
    {
        return $this->belongsTo(SubtipoIncidencia::class, 'id_subtipo', 'id_subtipo');
    }

    public function estado()
    {
        return $this->belongsTo(Estado::class, 'id_estado_actual', 'id_estado');
    }

    public function zona()
    {
        return $this->belongsTo(Zona::class, 'id_zona', 'id_zona');
    }

    public function creador()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_creador', 'id_usuario');
    }

    public function adminRevisor()
    {
        return $this->belongsTo(Usuario::class, 'id_admin_revisor', 'id_usuario');
    }

    public function asignaciones()
    {
        return $this->hasMany(IncidenciaAsignacion::class, 'id_incidencia', 'id_incidencia');
    }

    public function apoyos()
    {
        return $this->hasMany(IncidenciaApoyo::class, 'id_incidencia', 'id_incidencia');
    }

    public function historialEstados()
    {
        return $this->hasMany(IncidenciaEstadoHistorial::class, 'id_incidencia', 'id_incidencia');
    }

    public function historialAprobaciones()
    {
        return $this->hasMany(IncidenciaAprobacionHistorial::class, 'id_incidencia', 'id_incidencia');
    }

    public function comentarios()
    {
        return $this->hasMany(IncidenciaComentario::class, 'id_incidencia', 'id_incidencia');
    }
}
