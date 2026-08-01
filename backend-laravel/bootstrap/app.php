<?php

use App\Http\Middleware\SoloAdmin;
use App\Http\Middleware\SanitizeAndDetectAttacks;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule) {
        // Respaldo del mecanismo perezoso que corre en cada petición:
        // si el contenedor tiene cron/`schedule:run` configurado, esto
        // garantiza la aprobación automática aunque no haya tráfico.
        $schedule->command('incidencias:auto-aprobar')->everyFiveMinutes()->withoutOverlapping();
    })
    ->withMiddleware(function (Middleware $middleware) {
        // Exclusión de CSRF para la API
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);

        $middleware->api(prepend: [
            SecurityHeaders::class,
            SanitizeAndDetectAttacks::class,
        ]);

        // Registro de tus alias de middleware
        $middleware->alias([
            'solo.admin' => SoloAdmin::class,
            'roles' => \App\Http\Middleware\RolesPermitidos::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'mensaje' => 'No autenticado o sesión expirada.'
                ], 401);
            }
        });

        // Para peticiones a la API, mostramos el mensaje real del error en
        // lugar del genérico "Server Error" que Laravel usa por defecto con
        // APP_DEBUG=false. Esto permite diagnosticar fallas sin necesidad de
        // entrar a los logs del contenedor por consola.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (!($request->is('api/*') || $request->expectsJson())) {
                return null;
            }
            $status = 500;
            if (method_exists($e, 'getStatusCode')) {
                $status = $e->getStatusCode();
            } elseif ($e instanceof \Illuminate\Validation\ValidationException) {
                $status = 422;
            }
            return response()->json([
                'ok' => false,
                'mensaje' => $e->getMessage() ?: 'Ocurrió un error en el servidor.',
                'tipo_error' => get_class($e),
            ], $status);
        });
    })->create();