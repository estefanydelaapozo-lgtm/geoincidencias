<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoIncidencia extends Model
{
    protected $table = 'tipos_incidencia';
    protected $primaryKey = 'id_tipo';
    public $timestamps = false;
    protected $fillable = ['nombre', 'descripcion', 'icono', 'color', 'activo', 'id_rol_responsable'];
    protected $casts = ['activo' => 'boolean'];

    public function subtipos()
    {
        return $this->hasMany(SubtipoIncidencia::class, 'id_tipo', 'id_tipo');
    }

    public function rolResponsable()
    {
        return $this->belongsTo(Rol::class, 'id_rol_responsable', 'id_rol');
    }
}
