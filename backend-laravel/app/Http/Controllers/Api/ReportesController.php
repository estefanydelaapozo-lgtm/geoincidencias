<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReportesController extends Controller
{
    private function filtros(Request $request): array
    {
        return $request->validate([
            'desde' => ['nullable','date_format:Y-m-d'],
            'hasta' => ['nullable','date_format:Y-m-d','after_or_equal:desde'],
            'tipo' => ['nullable','integer','exists:tipos_incidencia,id_tipo'],
            'zona' => ['nullable','integer','exists:zonas,id_zona'],
            'prioridad' => ['nullable', Rule::in(['Baja','Media','Alta','Crítica'])],
        ]);
    }

    private function aplicarFiltros(Builder $query, array $filtros): Builder
    {
        $query->where('i.estado_aprobacion', 'aprobada');
        if (!empty($filtros['desde'])) $query->whereDate('i.fecha_ocurrencia', '>=', $filtros['desde']);
        if (!empty($filtros['hasta'])) $query->whereDate('i.fecha_ocurrencia', '<=', $filtros['hasta']);
        if (!empty($filtros['tipo']))  $query->where('i.id_tipo', $filtros['tipo']);
        if (!empty($filtros['zona']))  $query->where('i.id_zona', $filtros['zona']);
        if (!empty($filtros['prioridad'])) $query->where('i.prioridad', $filtros['prioridad']);
        return $query;
    }

    public function resumen(Request $request)
    {
        $f = $this->filtros($request);
        $base = $this->aplicarFiltros(DB::table('incidencias as i')
            ->join('estados as e', 'i.id_estado_actual', '=', 'e.id_estado'), $f);

        $r = (clone $base)->selectRaw("COUNT(*) total,
            COALESCE(SUM(CASE WHEN e.nombre IN ('Resuelta','Cerrada') THEN 1 ELSE 0 END),0) resueltas,
            COALESCE(SUM(CASE WHEN i.prioridad='Alta' THEN 1 ELSE 0 END),0) criticas,
            COALESCE(AVG(i.tiempo_resolucion_horas),0) tiempo_promedio_resolucion")->first();

        $porPrioridad = (clone $base)->select('i.prioridad', DB::raw('COUNT(*) total'))
            ->groupBy('i.prioridad')->orderByDesc('total')->get();

        $total = (int) ($r->total ?? 0);
        $resueltas = (int) ($r->resueltas ?? 0);

        return response()->json([
            'total' => $total,
            'resueltas' => $resueltas,
            'criticas' => (int) ($r->criticas ?? 0),
            'porcentaje_resueltas' => $total ? round($resueltas * 100 / $total, 1) : 0,
            'tiempo_promedio_resolucion' => round((float) ($r->tiempo_promedio_resolucion ?? 0), 2),
            'dias_promedio' => round(((float) ($r->tiempo_promedio_resolucion ?? 0)) / 24, 2),
            'prioridad_predominante' => $porPrioridad->first()?->prioridad,
            'por_prioridad' => $porPrioridad->map(fn($p) => [
                'prioridad' => $p->prioridad, 'total' => (int) $p->total,
                'porcentaje' => $total ? round($p->total * 100 / $total, 1) : 0,
            ]),
        ]);
    }

    private function conPorcentaje($coleccion, string $campoTotal = 'total')
    {
        $suma = $coleccion->sum($campoTotal);
        return $coleccion->map(function ($fila) use ($suma, $campoTotal) {
            $arr = (array) $fila;
            $arr['total'] = (int) $arr[$campoTotal];
            $arr['porcentaje'] = $suma ? round($arr[$campoTotal] * 100 / $suma, 1) : 0;
            return $arr;
        })->values();
    }

    public function porCategoria(Request $request)
    {
        $q = $this->aplicarFiltros(DB::table('incidencias as i')
            ->join('tipos_incidencia as ti', 'i.id_tipo', '=', 'ti.id_tipo'), $this->filtros($request));
        $datos = $q->select('ti.nombre as categoria','ti.nombre as tipo',DB::raw('COUNT(*) total'))
            ->groupBy('ti.id_tipo','ti.nombre')->orderByDesc('total')->get();
        return response()->json($this->conPorcentaje($datos));
    }

    public function porEstado(Request $request)
    {
        $q = $this->aplicarFiltros(DB::table('incidencias as i')
            ->join('estados as e', 'i.id_estado_actual', '=', 'e.id_estado'), $this->filtros($request));
        $datos = $q->select('e.nombre as estado','e.color',DB::raw('COUNT(*) total'))
            ->groupBy('e.id_estado','e.nombre','e.color')->orderBy('e.orden')->get();
        return response()->json($this->conPorcentaje($datos));
    }

    // Porcentaje por zona/parroquia (punto 3 del pedido).
    public function porZona(Request $request)
    {
        $q = $this->aplicarFiltros(DB::table('incidencias as i')
            ->join('zonas as z', 'i.id_zona', '=', 'z.id_zona'), $this->filtros($request));
        $datos = $q->select('z.nombre as zona', DB::raw('COUNT(*) total'))
            ->groupBy('z.id_zona','z.nombre')->orderByDesc('total')->get();
        return response()->json($this->conPorcentaje($datos));
    }

    public function tendencia(Request $request)
    {
        $q = $this->aplicarFiltros(DB::table('incidencias as i'), $this->filtros($request));
        $expr = "DATE_FORMAT(i.fecha_ocurrencia, '%Y-%m')";
        return response()->json($q->selectRaw("{$expr} mes, COUNT(*) total")
            ->groupByRaw($expr)->orderByRaw($expr)->get());
    }

    // Porcentaje anual (punto 3 del pedido: porcentaje mensual/anual).
    public function porAnual(Request $request)
    {
        $q = $this->aplicarFiltros(DB::table('incidencias as i'), $this->filtros($request));
        $datos = $q->selectRaw("YEAR(i.fecha_ocurrencia) anio, COUNT(*) total")
            ->groupByRaw('YEAR(i.fecha_ocurrencia)')->orderByRaw('YEAR(i.fecha_ocurrencia)')->get();
        return response()->json($this->conPorcentaje($datos));
    }

    public function porResponsable(Request $request)
    {
        $q = DB::table('incidencia_asignaciones as ia')
            ->join('usuarios as u', 'ia.id_usuario', '=', 'u.id_usuario')
            ->join('incidencias as i', 'ia.id_incidencia', '=', 'i.id_incidencia')
            ->join('estados as e', 'i.id_estado_actual', '=', 'e.id_estado')
            ->where('ia.rol_asignacion', 'responsable');
        $q = $this->aplicarFiltros($q, $this->filtros($request));
        $datos = $q->groupBy('u.id_usuario','u.nombre','u.apellido','u.rol')
            ->selectRaw("CONCAT(u.nombre,' ',IFNULL(u.apellido,'')) responsable, u.rol,
                COUNT(DISTINCT ia.id_incidencia) asignadas,
                COALESCE(SUM(CASE WHEN e.nombre IN ('Resuelta','Cerrada') THEN 1 ELSE 0 END),0) resueltas,
                COALESCE(SUM(CASE WHEN e.nombre='En proceso' THEN 1 ELSE 0 END),0) en_proceso")
            ->orderByDesc('asignadas')->get()
            ->map(function ($f) {
                $f = (array) $f;
                $f['porcentaje_resueltas'] = $f['asignadas'] ? round($f['resueltas'] * 100 / $f['asignadas'], 1) : 0;
                return $f;
            });
        return response()->json($datos->values());
    }
}
