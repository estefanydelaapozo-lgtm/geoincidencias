<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidenciaAprobacionHistorial extends Model
{
    protected $table = 'incidencia_aprobaciones_historial';
    protected $primaryKey = 'id_historial_aprobacion';
    public $timestamps = false;
    protected $fillable = ['id_incidencia', 'id_usuario', 'accion', 'motivo'];
    protected $casts = ['fecha' => 'datetime'];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function incidencia()
    {
        return $this->belongsTo(Incidencia::class, 'id_incidencia', 'id_incidencia');
    }
}
