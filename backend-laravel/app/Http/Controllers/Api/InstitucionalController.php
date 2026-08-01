<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Incidencia;
use App\Models\Rol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InstitucionalController extends Controller
{
    public function resumen(Request $request)
    {
        $u=$request->user()->loadMissing('rolDetalle');
        abort_unless($u->rolDetalle?->es_institucional || in_array($u->rol,['admin','supervisor'],true),403);
        $q=Incidencia::query()->where('estado_aprobacion','aprobada');
        if ($u->rol === 'tecnico') {
            $q->whereHas('asignaciones', fn($x)=>$x->where('id_usuario',$u->id_usuario));
        } elseif (!in_array($u->rol,['admin','supervisor'],true)) {
            $q->whereHas('tipo', fn($x)=>$x->where('id_rol_responsable',$u->id_rol));
        }
        $total=(clone $q)->count();
        $pendientes=(clone $q)->whereHas('estado',fn($x)=>$x->whereIn('nombre',['Registrada','En proceso']))->count();
        $resueltas=(clone $q)->whereHas('estado',fn($x)=>$x->whereIn('nombre',['Resuelta','Cerrada']))->count();
        $porEstado=(clone $q)->join('estados','incidencias.id_estado_actual','=','estados.id_estado')
            ->select('estados.nombre',DB::raw('COUNT(*) total'))->groupBy('estados.id_estado','estados.nombre')->get();
        $ultimas=(clone $q)->with(['tipo:id_tipo,nombre','estado:id_estado,nombre,color','zona:id_zona,nombre'])
            ->latest('fecha_registro')->limit(20)->get(['id_incidencia','titulo','prioridad','id_tipo','id_estado_actual','id_zona','fecha_registro']);
        return response()->json([
            'rol'=>$u->rolDetalle,
            'total'=>$total,'pendientes'=>$pendientes,'resueltas'=>$resueltas,
            'por_estado'=>$porEstado,'ultimas'=>$ultimas,
        ]);
    }

    public function roles()
    {
        return Rol::where('activo',1)->orderBy('nombre')->get(['id_rol','slug','nombre','descripcion','color','icono','es_institucional']);
    }
}
