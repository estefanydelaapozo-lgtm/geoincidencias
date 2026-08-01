<?php

namespace App\Http\Middleware;

use App\Services\SecurityAuditService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeAndDetectAttacks
{
    private const EXCLUDED_KEYS = ['password', 'password_actual', 'password_nuevo', 'password_confirmation', 'token'];

    public function handle(Request $request, Closure $next): Response
    {
        $payload = $request->all();
        $suspicious = $this->containsAttackPattern($payload);

        if ($suspicious) {
            SecurityAuditService::record('suspicious_input', $request, [
                'path' => $request->path(),
                'keys' => array_keys($payload),
            ]);
        }

        $request->merge($this->sanitizeArray($payload));

        return $next($request);
    }

    private function sanitizeArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (in_array((string) $key, self::EXCLUDED_KEYS, true)) {
                continue;
            }
            if (is_array($value)) {
                $data[$key] = $this->sanitizeArray($value);
            } elseif (is_string($value)) {
                $value = str_replace("\0", '', $value);
                $data[$key] = trim(strip_tags($value));
            }
        }
        return $data;
    }

    private function containsAttackPattern(array $data): bool
    {
        $text = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        return (bool) preg_match(
            '/(<script\b|javascript:|onerror\s*=|onload\s*=|\bunion\s+select\b|\bdrop\s+table\b|\bor\s+1\s*=\s*1\b|--\s|\/\*|\*\/)/i',
            $text
        );
    }
}
