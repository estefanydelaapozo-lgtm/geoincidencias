<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HistorialActividad;
use App\Models\Incidencia;
use App\Models\IncidenciaComentario;
use Illuminate\Http\Request;

class ComentariosController extends Controller
{
    private function puedeVerOComentar(Incidencia $inc, $usuario): bool
    {
        return in_array($usuario->rol, ['admin', 'supervisor'], true)
            || $inc->id_usuario_creador === $usuario->id_usuario
            || $inc->asignaciones()->where('id_usuario', $usuario->id_usuario)->exists();
    }

    public function index(Request $request, int $id)
    {
        $inc = Incidencia::findOrFail($id);
        abort_unless($this->puedeVerOComentar($inc, $request->user()), 403, 'No tienes permiso para ver el seguimiento de esta incidencia.');

        $datos = IncidenciaComentario::query()
            ->with('usuario:id_usuario,nombre,apellido,rol')
            ->where('id_incidencia', $id)
            ->orderBy('fecha')
            ->get()
            ->map(fn ($comentario) => [
                'id_comentario' => $comentario->id_comentario,
                'comentario' => $comentario->comentario,
                'fecha' => optional($comentario->fecha)->format('Y-m-d H:i:s'),
                'autor' => $comentario->usuario?->nombre_completo ?? 'Sistema',
                'rol' => $comentario->usuario?->rol,
            ]);

        return response()->json(['ok' => true, 'datos' => $datos]);
    }

    public function store(Request $request, int $id)
    {
        $inc = Incidencia::findOrFail($id);
        abort_unless($this->puedeVerOComentar($inc, $request->user()), 403, 'No tienes permiso para comentar esta incidencia.');

        $data = $request->validate([
            'comentario' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $comentario = IncidenciaComentario::create([
            'id_incidencia' => $id,
            'id_usuario' => $request->user()->id_usuario,
            'comentario' => trim($data['comentario']),
        ]);

        HistorialActividad::registrar(
            $request->user()->id_usuario,
            $id,
            'agrego_comentario',
            "Se agregó un comentario de seguimiento a la incidencia #{$id}",
            $request->ip()
        );

        return response()->json([
            'ok' => true,
            'mensaje' => 'Comentario agregado correctamente.',
            'id' => $comentario->id_comentario,
        ], 201);
    }
}
