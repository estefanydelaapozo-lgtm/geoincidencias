<?php

namespace App\Http\Middleware;

use App\Services\SecurityAuditService;
use Closure;
use Illuminate\Http\Request;

class SoloAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user() || $request->user()->rol !== 'admin') {
            SecurityAuditService::record('unauthorized_admin_access', $request, [
                'required_role' => 'admin',
                'actual_role' => $request->user()?->rol,
            ]);
            return response()->json(['ok' => false, 'mensaje' => 'Acceso restringido a administradores.'], 403);
        }
        return $next($request);
    }
}
