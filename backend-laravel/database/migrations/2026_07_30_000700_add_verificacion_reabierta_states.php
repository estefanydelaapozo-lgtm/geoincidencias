<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Migración aditiva: agrega los estados 'En verificación' y 'Reabierta' que
// pedía el flujo de incidencias (punto 6 y 7 del pedido). No modifica ni
// elimina los estados existentes, por lo que no rompe datos ni historiales.
return new class extends Migration
{
    public function up(): void
    {
        $estados = [
            ['nombre' => 'En verificación', 'descripcion' => 'El supervisor está validando la evidencia del técnico', 'color' => '#0ea5e9', 'orden' => 5, 'activo' => 1],
            ['nombre' => 'Reabierta', 'descripcion' => 'El ciudadano solicitó revisión y el supervisor reabrió la incidencia', 'color' => '#f97316', 'orden' => 6, 'activo' => 1],
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
