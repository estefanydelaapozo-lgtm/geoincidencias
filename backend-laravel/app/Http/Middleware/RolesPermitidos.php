<?php
namespace App\Http\Middleware;
use App\Services\SecurityAuditService;
use Closure;
use Illuminate\Http\Request;

class RolesPermitidos
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $rol=$request->user()?->rol;
        if (!$rol || !in_array($rol,$roles,true)) {
            SecurityAuditService::record('unauthorized_role_access',$request,['required'=>$roles,'actual'=>$rol]);
            return response()->json(['ok'=>false,'mensaje'=>'Tu institución no tiene permiso para esta operación.'],403);
        }
        return $next($request);
    }
}
