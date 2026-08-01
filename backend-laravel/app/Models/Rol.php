<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    protected $table='roles';
    protected $primaryKey='id_rol';
    protected $fillable=['slug','nombre','descripcion','color','icono','es_institucional','activo'];
    protected $casts=['es_institucional'=>'boolean','activo'=>'boolean'];
    public function usuarios(){ return $this->hasMany(Usuario::class,'id_rol','id_rol'); }
    public function tipos(){ return $this->hasMany(TipoIncidencia::class,'id_rol_responsable','id_rol'); }
}
