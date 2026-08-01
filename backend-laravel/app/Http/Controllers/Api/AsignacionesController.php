<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HistorialActividad;
use App\Models\Incidencia;
use App\Models\IncidenciaAsignacion;
use App\Models\Usuario;
use Illuminate\Http\Request;

class AsignacionesController extends Controller
{
    public function index(int $id)
    {
        Incidencia::findOrFail($id);

        $datos = IncidenciaAsignacion::query()
            ->with('usuario:id_usuario,nombre,apellido,correo,rol')
            ->where('id_incidencia', $id)
            ->orderBy('fecha_asignacion')
            ->get();

        return response()->json(['ok' => true, 'datos' => $datos]);
    }

    public function store(Request $request, int $id)
    {
        Incidencia::findOrFail($id);

        $data = $request->validate([
            'id_usuario' => ['required', 'integer', 'exists:usuarios,id_usuario'],
            'rol_asignacion' => ['required', 'in:responsable,apoyo'],
        ]);

        Usuario::whereKey($data['id_usuario'])->where('activo', 1)->firstOrFail();

        $asignacion = IncidenciaAsignacion::updateOrCreate(
            ['id_incidencia' => $id, 'id_usuario' => $data['id_usuario']],
            ['rol_asignacion' => $data['rol_asignacion']]
        );

        HistorialActividad::registrar(
            $request->user()->id_usuario,
            $id,
            'asigno_usuario',
            "Usuario asignado con rol {$data['rol_asignacion']} a la incidencia #{$id}",
            $request->ip()
        );

        return response()->json([
            'ok' => true,
            'mensaje' => 'Asignación guardada correctamente.',
            'id' => $asignacion->id_asignacion,
        ]);
    }

    public function destroy(Request $request, int $id, int $idUsuario)
    {
        $eliminados = IncidenciaAsignacion::where('id_incidencia', $id)
            ->where('id_usuario', $idUsuario)
            ->delete();

        abort_if($eliminados === 0, 404, 'Asignación no encontrada.');

        HistorialActividad::registrar(
            $request->user()->id_usuario,
            $id,
            'quito_asignacion',
            "Se eliminó una asignación de la incidencia #{$id}",
            $request->ip()
        );

        return response()->json(['ok' => true, 'mensaje' => 'Asignación eliminada.']);
    }
}
