<?php
namespace App\Policies;
use App\Models\Incidencia;
use App\Models\Usuario;

class IncidenciaPolicy
{
    public function update(Usuario $usuario, Incidencia $incidencia): bool
    {
        if (in_array($usuario->rol, ['admin', 'supervisor'], true)) return true;
        // El ciudadano solo puede editar los datos de su incidencia mientras
        // sigue pendiente de validación (punto 14 del pedido).
        return $incidencia->id_usuario_creador === $usuario->id_usuario
            && $incidencia->estado_aprobacion === 'pendiente_revision';
    }

    // Reabrir: administrador (por motivos administrativos) o supervisor.
    public function reopen(Usuario $usuario, Incidencia $incidencia): bool
    {
        return $usuario->rol === 'supervisor';
    }
    public function changeState(Usuario $usuario, Incidencia $incidencia): bool
    {
        if($usuario->rol === 'supervisor') return true;
        return $usuario->esInstitucional() && $incidencia->asignaciones()->where('id_usuario',$usuario->id_usuario)->exists();
    }

    // El Administrador y el Supervisor tienen los mismos permisos de aprobación.
    public function approve(Usuario $usuario, Incidencia $incidencia): bool
    {
        return in_array($usuario->rol, ['admin', 'supervisor'], true);
    }

    // Rechazar solo está permitido dentro de las primeras 24 horas desde el registro.
    public function reject(Usuario $usuario, Incidencia $incidencia): bool
    {
        return in_array($usuario->rol, ['admin', 'supervisor'], true)
            && $incidencia->estado_aprobacion === 'pendiente_revision'
            && $incidencia->fecha_limite_accion
            && now()->lessThan($incidencia->fecha_limite_accion);
    }

    // El administrador puede eliminar cualquier incidencia en cualquier momento.
    // El supervisor conserva la restricción original: solo dentro de las primeras
    // 24 horas desde el registro y mientras siga pendiente de revisión.
    public function delete(Usuario $usuario, Incidencia $incidencia): bool
    {
        if ($usuario->rol === 'admin') return true;

        return $usuario->rol === 'supervisor'
            && $incidencia->estado_aprobacion === 'pendiente_revision'
            && $incidencia->fecha_limite_accion
            && now()->lessThan($incidencia->fecha_limite_accion);
    }
}
