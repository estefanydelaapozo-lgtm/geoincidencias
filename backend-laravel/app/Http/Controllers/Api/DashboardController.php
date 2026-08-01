<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    // Si el usuario logueado es institucional (no admin/supervisor), devuelve
    // los datos necesarios para filtrar el dashboard a su área. Si es
    // admin/supervisor/ciudadano, devuelve null (dashboard global, sin filtro).
    private function filtroInstitucional(Request $request): ?array
    {
        $u = $request->user()->loadMissing('rolDetalle');
        if (!$u->rolDetalle?->es_institucional || in_array($u->rol, ['admin', 'supervisor'], true)) {
            return null;
        }
        return $u->rol === 'tecnico'
            ? ['tipo' => 'tecnico', 'id_usuario' => $u->id_usuario]
            : ['tipo' => 'rol', 'id_rol' => $u->id_rol];
    }

    private function claveCache(string $base, ?array $filtro): string
    {
        if (!$filtro) return $base;
        return $filtro['tipo'] === 'tecnico'
            ? "{$base}.tecnico.{$filtro['id_usuario']}"
            : "{$base}.rol.{$filtro['id_rol']}";
    }

    public function resumen(Request $request)
    {
        $filtro = $this->filtroInstitucional($request);
        return response()->json(Cache::remember(
            $this->claveCache('dashboard.resumen', $filtro),
            now()->addSeconds(30),
            fn () => $this->calcularResumen($filtro)
        ));
    }

    private function calcularResumen(?array $filtro): array
    {
        $q = DB::table('incidencias as i')->join('estados as e', 'i.id_estado_actual', '=', 'e.id_estado');
        $this->aplicarFiltro($q, $filtro, 'i');

        $r = $q->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN e.nombre='Registrada' THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN e.nombre='En proceso' THEN 1 ELSE 0 END) as en_proceso,
                SUM(CASE WHEN e.nombre='Resuelta' THEN 1 ELSE 0 END) as resueltas,
                SUM(CASE WHEN e.nombre='Cerrada' THEN 1 ELSE 0 END) as cerradas,
                SUM(CASE WHEN e.nombre='Reabierta' THEN 1 ELSE 0 END) as reabiertas,
                SUM(CASE WHEN i.estado_aprobacion='rechazada' THEN 1 ELSE 0 END) as rechazadas,
                SUM(CASE WHEN i.prioridad='Alta' THEN 1 ELSE 0 END) as alta_prioridad,
                SUM(CASE WHEN i.estado_aprobacion='pendiente_revision' THEN 1 ELSE 0 END) as pendientes_aprobacion
            ")
            ->first();

        $total = (int) ($r->total ?? 0);
        $porcentaje = fn ($n) => $total ? round(((int) $n) * 100 / $total, 1) : 0;

        return [
            'total' => $total,
            'pendientes' => (int) $r->pendientes, 'porcentaje_pendientes' => $porcentaje($r->pendientes),
            'en_proceso' => (int) $r->en_proceso, 'porcentaje_en_proceso' => $porcentaje($r->en_proceso),
            'resueltas' => (int) $r->resueltas, 'porcentaje_resueltas' => $porcentaje($r->resueltas),
            'cerradas' => (int) $r->cerradas, 'porcentaje_cerradas' => $porcentaje($r->cerradas),
            'reabiertas' => (int) $r->reabiertas, 'porcentaje_reabiertas' => $porcentaje($r->reabiertas),
            'rechazadas' => (int) $r->rechazadas, 'porcentaje_rechazadas' => $porcentaje($r->rechazadas),
            'alta_prioridad' => (int) $r->alta_prioridad,
            'pendientes_aprobacion' => (int) $r->pendientes_aprobacion,
        ];
    }

    // Aplica el filtro institucional directamente sobre la tabla de incidencias
    // (alias $aliasIncidencias), ya sea con WHERE (consulta directa) o dejando
    // el closure para usarlo dentro del ON de un LEFT JOIN.
    private function aplicarFiltro($query, ?array $filtro, string $aliasIncidencias): void
    {
        if (!$filtro) return;
        if ($filtro['tipo'] === 'tecnico') {
            $query->whereExists(function ($sub) use ($filtro, $aliasIncidencias) {
                $sub->select(DB::raw(1))->from('incidencia_asignaciones as ia')
                    ->whereColumn('ia.id_incidencia', "{$aliasIncidencias}.id_incidencia")
                    ->where('ia.id_usuario', $filtro['id_usuario']);
            });
        } else {
            $query->whereExists(function ($sub) use ($filtro, $aliasIncidencias) {
                $sub->select(DB::raw(1))->from('tipos_incidencia as ti2')
                    ->whereColumn('ti2.id_tipo', "{$aliasIncidencias}.id_tipo")
                    ->where('ti2.id_rol_responsable', $filtro['id_rol']);
            });
        }
    }

    public function porTipo(Request $request)
    {
        $filtro = $this->filtroInstitucional($request);
        $key = $this->claveCache('dashboard.por_tipo', $filtro);
        $datos = Cache::remember($key, now()->addSeconds(30), function () use ($filtro) {
            $q = DB::table('tipos_incidencia as ti')
                ->leftJoin('incidencias as i', function ($join) use ($filtro) {
                    $join->on('ti.id_tipo', '=', 'i.id_tipo')->where('i.estado_aprobacion', '=', 'aprobada');
                    if ($filtro) $this->aplicarFiltro($join, $filtro, 'i');
                });
            if ($filtro && $filtro['tipo'] === 'rol') {
                $q->where('ti.id_rol_responsable', $filtro['id_rol']);
            }
            return $q->groupBy('ti.nombre')
                ->orderByDesc(DB::raw('COUNT(i.id_incidencia)'))
                ->select('ti.nombre as tipo', DB::raw('COUNT(i.id_incidencia) as total'))
                ->get();
        });
        return response()->json($datos);
    }

    public function porEstado(Request $request)
    {
        $filtro = $this->filtroInstitucional($request);
        $key = $this->claveCache('dashboard.por_estado', $filtro);
        $datos = Cache::remember($key, now()->addSeconds(30), function () use ($filtro) {
            return DB::table('estados as e')
                ->leftJoin('incidencias as i', function ($join) use ($filtro) {
                    $join->on('e.id_estado', '=', 'i.id_estado_actual')->where('i.estado_aprobacion', '=', 'aprobada');
                    if ($filtro) $this->aplicarFiltro($join, $filtro, 'i');
                })
                ->groupBy('e.nombre', 'e.color')
                ->select('e.nombre as estado', 'e.color', DB::raw('COUNT(i.id_incidencia) as total'))
                ->get();
        });
        return response()->json($datos);
    }

    public function porZona(Request $request)
    {
        $filtro = $this->filtroInstitucional($request);
        $key = $this->claveCache('dashboard.por_zona', $filtro);
        $datos = Cache::remember($key, now()->addSeconds(30), function () use ($filtro) {
            return DB::table('zonas as z')
                ->leftJoin('incidencias as i', function ($join) use ($filtro) {
                    $join->on('z.id_zona', '=', 'i.id_zona')->where('i.estado_aprobacion', '=', 'aprobada');
                    if ($filtro) $this->aplicarFiltro($join, $filtro, 'i');
                })
                ->groupBy('z.nombre')
                ->orderByDesc(DB::raw('COUNT(i.id_incidencia)'))
                ->select('z.nombre as zona', DB::raw('COUNT(i.id_incidencia) as total'))
                ->get();
        });
        return response()->json($datos);
    }

    public function ultimas(Request $request)
    {
        $filtro = $this->filtroInstitucional($request);
        $key = $this->claveCache('dashboard.ultimas', $filtro);
        $datos = Cache::remember($key, now()->addSeconds(30), function () use ($filtro) {
            $q = DB::table('incidencias as i')
                ->join('tipos_incidencia as ti', 'i.id_tipo', '=', 'ti.id_tipo')
                ->join('estados as e', 'i.id_estado_actual', '=', 'e.id_estado')
                ->join('zonas as z', 'i.id_zona', '=', 'z.id_zona')
                ->where('i.estado_aprobacion', 'aprobada');
            $this->aplicarFiltro($q, $filtro, 'i');
            return $q->orderByDesc('i.fecha_registro')
                ->limit(5)
                ->select('i.id_incidencia', 'i.titulo', 'ti.nombre as tipo', 'z.nombre as zona', 'e.nombre as estado', 'i.prioridad', 'i.fecha_ocurrencia')
                ->get();
        });
        return response()->json($datos);
    }
}
