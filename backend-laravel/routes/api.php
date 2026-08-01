<?php

use App\Http\Controllers\Api\ApoyosController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CatalogosController;
use App\Http\Controllers\Api\ComentariosController;
use App\Http\Controllers\Api\AsignacionesController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\HistorialController;
use App\Http\Controllers\Api\InstitucionalController;
use App\Http\Controllers\Api\IncidenciasController;
use App\Http\Controllers\Api\NotificacionesController;
use App\Http\Controllers\Api\ReportesController;
use App\Http\Controllers\Api\UsuariosController;
use Illuminate\Support\Facades\Route;

// ── Salud del servicio ──
Route::get('/health', fn () => response()->json(['ok' => true, 'mensaje' => 'Backend funcionando correctamente.']));

// ── Auth (público) ──
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/registro', [AuthController::class, 'registro']);
Route::get('/auth/google/config', [AuthController::class, 'googleConfig']);
Route::post('/auth/google', [AuthController::class, 'google']);
Route::get('/auth/foto-perfil/{id}', [AuthController::class, 'fotoPerfil'])->whereNumber('id');

// ── Rutas protegidas (requieren token Sanctum) ──
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/auth/perfil', [AuthController::class, 'perfil']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/foto-perfil', [AuthController::class, 'subirFotoPerfil']);
    Route::post('/auth/cambiar-password', [UsuariosController::class, 'cambiarPassword']);

    // Catálogos
    Route::get('/catalogos/tipos', [CatalogosController::class, 'tipos']);
    Route::get('/catalogos/subtipos/{id_tipo}', [CatalogosController::class, 'subtipos']);
    Route::get('/catalogos/estados', [CatalogosController::class, 'estados']);
    Route::get('/catalogos/zonas', [CatalogosController::class, 'zonas']);
    Route::get('/catalogos/usuarios', [CatalogosController::class, 'usuarios']);
    Route::get('/catalogos/incentivos', [CatalogosController::class, 'incentivos']);
    Route::get('/catalogos/roles', [InstitucionalController::class, 'roles']);

    // Incidencias
    Route::get('/incidencias', [IncidenciasController::class, 'index']);
    Route::get('/incidencias/mapa', [IncidenciasController::class, 'mapa']);
    Route::get('/incidencias/exportar/csv', [IncidenciasController::class, 'exportarCsv']);
    Route::middleware('roles:admin,supervisor')->get('/incidencias/pendientes-aprobacion', [IncidenciasController::class, 'pendientesAprobacion']);
    Route::get('/incidencias/{id}', [IncidenciasController::class, 'show']);
    Route::post('/incidencias', [IncidenciasController::class, 'store']);
    Route::put('/incidencias/{id}', [IncidenciasController::class, 'update']);
    Route::put('/incidencias/{id}/estado', [IncidenciasController::class, 'cambiarEstado']);
    Route::post('/incidencias/{id}/foto', [IncidenciasController::class, 'subirFoto'])->whereNumber('id');
    Route::get('/incidencias/{id}/foto', [IncidenciasController::class, 'foto'])->whereNumber('id');
    Route::get('/incidencias/{id}/comentarios', [ComentariosController::class, 'index']);
    Route::post('/incidencias/{id}/comentarios', [ComentariosController::class, 'store']);
    Route::get('/incidencias/{id}/asignaciones', [AsignacionesController::class, 'index']);

    // Apoyos / incentivos (usuario)
    Route::post('/apoyos', [ApoyosController::class, 'store']);
    Route::get('/apoyos/mis-apoyos', [ApoyosController::class, 'misApoyos']);
    Route::get('/apoyos/mi-saldo', [ApoyosController::class, 'miSaldo']);

    // Panel por institución
    Route::middleware('roles:admin,supervisor,tecnico,policia,bomberos,salud,electrica,agua,obras_publicas,medio_ambiente')->get('/institucional/resumen', [InstitucionalController::class, 'resumen']);

    // Dashboard y reportes
    Route::get('/dashboard/resumen', [DashboardController::class, 'resumen']);
    Route::get('/dashboard/por-tipo', [DashboardController::class, 'porTipo']);
    Route::get('/dashboard/por-estado', [DashboardController::class, 'porEstado']);
    Route::get('/dashboard/por-zona', [DashboardController::class, 'porZona']);
    Route::get('/dashboard/ultimas', [DashboardController::class, 'ultimas']);

    Route::get('/reportes/resumen', [ReportesController::class, 'resumen']);
    Route::get('/reportes/por-categoria', [ReportesController::class, 'porCategoria']);
    Route::get('/reportes/por-estado', [ReportesController::class, 'porEstado']);
    Route::get('/reportes/tendencia', [ReportesController::class, 'tendencia']);
    Route::get('/reportes/por-zona', [ReportesController::class, 'porZona']);
    Route::get('/reportes/por-anual', [ReportesController::class, 'porAnual']);
    Route::get('/reportes/por-responsable', [ReportesController::class, 'porResponsable']);

    // Notificaciones
    Route::get('/notificaciones', [NotificacionesController::class, 'index']);
    Route::get('/notificaciones/no-leidas', [NotificacionesController::class, 'noLeidas']);
    Route::put('/notificaciones/{id}/leer', [NotificacionesController::class, 'marcarLeida']);
    Route::put('/notificaciones/leer-todas', [NotificacionesController::class, 'marcarTodasLeidas']);

    // Perfil propio o edición autorizada
    Route::get('/usuarios/{id}', [UsuariosController::class, 'show']);
    Route::put('/usuarios/{id}', [UsuariosController::class, 'update']);

    // Solicitud de revisión del ciudadano sobre su propia incidencia
    Route::post('/incidencias/{id}/solicitar-revision', [IncidenciasController::class, 'solicitarRevision']);

    // ── Aprobación de incidencias: Administrador y Supervisor tienen los mismos permisos ──
    Route::middleware('roles:admin,supervisor')->group(function () {
        Route::delete('/incidencias/{id}', [IncidenciasController::class, 'destroy']);
        Route::put('/incidencias/{id}/aprobar', [IncidenciasController::class, 'aprobar']);
        Route::put('/incidencias/{id}/rechazar', [IncidenciasController::class, 'rechazar']);
    });

    // ── Flujo operativo: solo Supervisor (el Administrador únicamente consulta,
    //    no asigna técnicos, no reasigna ni reabre incidencias) ──
    Route::middleware('roles:supervisor')->group(function () {
        Route::put('/incidencias/{id}/reabrir', [IncidenciasController::class, 'reabrir']);
        Route::put('/incidencias/{id}/asignar-tecnico', [IncidenciasController::class, 'asignarTecnico']);
        Route::post('/incidencias/{id}/asignaciones', [AsignacionesController::class, 'store']);
        Route::delete('/incidencias/{id}/asignaciones/{idUsuario}', [AsignacionesController::class, 'destroy']);
    });

    // ── Rutas exclusivas de administrador ──
    Route::middleware('solo.admin')->group(function () {
        Route::get('/apoyos', [ApoyosController::class, 'index']);
        Route::get('/apoyos/pendientes', [ApoyosController::class, 'pendientes']);
        Route::put('/apoyos/{id}/aprobar', [ApoyosController::class, 'aprobar']);
        Route::put('/apoyos/{id}/rechazar', [ApoyosController::class, 'rechazar']);

        Route::get('/admin/usuarios', [UsuariosController::class, 'index']);
        Route::post('/admin/usuarios', [UsuariosController::class, 'store']);
        Route::post('/admin/usuarios/crear-supervisor-demo', [UsuariosController::class, 'crearSupervisorDemo']);
        Route::post('/admin/usuarios/crear-tecnico-demo', [UsuariosController::class, 'crearTecnicoDemo']);
        Route::get('/admin/usuarios/{id}', [UsuariosController::class, 'show']);
        Route::put('/admin/usuarios/{id}', [UsuariosController::class, 'update']);
        Route::delete('/admin/usuarios/{id}', [UsuariosController::class, 'destroy']);

        Route::get('/historial', [HistorialController::class, 'index']);
        Route::get('/historial/acciones', [HistorialController::class, 'acciones']);
    });
});
