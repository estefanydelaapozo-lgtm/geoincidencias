<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $estados = [
            ['nombre' => 'Registrada', 'descripcion' => 'Incidencia reportada, aun no atendida', 'color' => '#ef4444', 'orden' => 1, 'activo' => 1],
            ['nombre' => 'En proceso', 'descripcion' => 'Incidencia siendo atendida por el responsable', 'color' => '#f59e0b', 'orden' => 2, 'activo' => 1],
            ['nombre' => 'Resuelta', 'descripcion' => 'Incidencia solucionada', 'color' => '#22c55e', 'orden' => 3, 'activo' => 1],
            ['nombre' => 'Cerrada', 'descripcion' => 'Incidencia verificada y cerrada oficialmente', 'color' => '#64748b', 'orden' => 4, 'activo' => 1],
        ];

        foreach ($estados as $estado) {
            DB::table('estados')->updateOrInsert(
                ['nombre' => $estado['nombre']],
                $estado
            );
        }
    }

    public function down(): void
    {
        // No se eliminan estados para no romper historiales o incidencias existentes.
    }
};
