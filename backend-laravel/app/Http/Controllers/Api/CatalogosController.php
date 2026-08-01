<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Estado;
use App\Models\IncentivoPrioridad;
use App\Models\SubtipoIncidencia;
use App\Models\TipoIncidencia;
use App\Models\Usuario;
use App\Models\Zona;
use Illuminate\Support\Facades\Cache;

class CatalogosController extends Controller
{
    // Cache de 1 hora para catálogos que no cambian frecuentemente
    protected $cacheTime = 3600;

    public function tipos()
    {
        return Cache::remember('catalogos.tipos', $this->cacheTime, function () {
            return TipoIncidencia::where('activo', 1)
                ->orderBy('nombre')
                ->get(['id_tipo as id', 'nombre', 'icono', 'color']);
        });
    }

    public function subtipos($id_tipo)
    {
        // Cache específico por tipo
        return Cache::remember("catalogos.subtipos.{$id_tipo}", $this->cacheTime, function () use ($id_tipo) {
            return SubtipoIncidencia::where('id_tipo', $id_tipo)
                ->where('activo', 1)
                ->orderBy('nombre')
                ->get(['id_subtipo as id', 'nombre']);
        });
    }

    public function estados()
    {
        return Cache::remember('catalogos.estados', $this->cacheTime, function () {
            return Estado::where('activo', 1)
                ->orderBy('orden')
                ->get(['id_estado as id', 'nombre', 'color']);
        });
    }

    public function zonas()
    {
        return Cache::remember('catalogos.zonas', $this->cacheTime, function () {
            return Zona::where('activo', 1)
                ->orderBy('nombre')
                ->get(['id_zona as id', 'nombre']);
        });
    }

    public function usuarios(\Illuminate\Http\Request $request)
    {
        // Filtro opcional ?institucional=1 para listar solo usuarios con rol
        // institucional (técnicos, policía, bomberos, etc.), usado por el
        // selector de "Asignar responsable" en el panel de incidencias.
        $soloInstitucionales = $request->boolean('institucional');
        $cacheKey = $soloInstitucionales ? 'catalogos.usuarios.institucionales' : 'catalogos.usuarios';

        // Cache más corto para usuarios (5 minutos)
        return Cache::remember($cacheKey, 300, function () use ($soloInstitucionales) {
            $q = Usuario::where('activo', 1)->orderBy('nombre');
            if ($soloInstitucionales) {
                $q->whereHas('rolDetalle', fn($r) => $r->where('es_institucional', 1));
            }
            return $q->selectRaw("id_usuario as id, CONCAT(nombre,' ',IFNULL(apellido,'')) as nombre, rol")
                ->get();
        });
    }

    public function incentivos()
    {
        return Cache::remember('catalogos.incentivos', $this->cacheTime, function () {
            return IncentivoPrioridad::all(['prioridad', 'monto']);
        });
    }
}
