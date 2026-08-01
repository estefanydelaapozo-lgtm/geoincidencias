<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Migración aditiva: agrega el estado 'Asignada' que faltaba del flujo
// original (Ciudadano → Supervisor aprueba → Asigna técnico → Asignada →
// técnico inicia trabajo → En proceso). No modifica los estados existentes.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('estados')->updateOrInsert(
            ['nombre' => 'Asignada'],
            ['descripcion' => 'Un técnico fue asignado y aún no inicia el trabajo', 'color' => '#7C3AED', 'orden' => 2, 'activo' => 1]
        );
        // Reordena el resto para que el listado quede cronológico (no afecta la lógica de transiciones).
        $orden = ['Registrada'=>1,'Asignada'=>2,'En proceso'=>3,'Resuelta'=>4,'En verificación'=>5,'Cerrada'=>6,'Reabierta'=>7];
        foreach ($orden as $nombre => $valor) {
            DB::table('estados')->where('nombre', $nombre)->update(['orden' => $valor]);
        }
    }

    public function down(): void
    {
        // No se elimina para no romper historiales o incidencias existentes.
    }
};
