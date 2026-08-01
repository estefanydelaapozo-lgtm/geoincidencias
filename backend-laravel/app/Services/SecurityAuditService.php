<?php

namespace App\Services;

use App\Models\SecurityAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SecurityAuditService
{
    public static function record(string $event, Request $request, array $context = []): void
    {
        $data = [
            'event' => $event,
            'ip' => $request->ip(),
            'user_id' => $request->user()?->id_usuario,
            'method' => $request->method(),
            'path' => $request->path(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            'context' => $context,
        ];
        Log::channel('security')->warning($event, $data);
        try {
            SecurityAuditLog::create([
                'evento' => $event,
                'id_usuario' => $data['user_id'],
                'ip_origen' => $data['ip'],
                'metodo' => $data['method'],
                'ruta' => $data['path'],
                'user_agent' => $data['user_agent'],
                'contexto' => $context,
            ]);
        } catch (\Throwable $e) {
            Log::channel('security')->error('security_audit_db_error', ['error' => $e->getMessage()]);
        }
    }
}
