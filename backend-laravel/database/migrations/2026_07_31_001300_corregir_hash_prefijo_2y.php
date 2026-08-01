<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

// Las dos correcciones anteriores (2026_07_31_001200 y este mismo archivo,
// en su primera versión) escribieron el hash de contraseña "a mano" copiado
// desde fuera de Laravel, y ninguno de los dos formatos terminó siendo
// exactamente el que Laravel exige. La forma correcta y a prueba de esto:
// pedirle a Laravel que genere el hash él mismo con Hash::make(), igual
// que hace el propio sistema al crear un usuario desde el formulario.
return new class extends Migration
{
    public function up(): void
    {
        $hashCorrecto = Hash::make('123456');

        $correos = [
            'supervisor@geoincidencias.com',
            'policia@geoincidencias.com',
            'bomberos@geoincidencias.com',
            'salud@geoincidencias.com',
            'electrica@geoincidencias.com',
            'agua@geoincidencias.com',
            'obras@geoincidencias.com',
            'ambiente@geoincidencias.com',
            'tecnico@geoincidencias.com',
        ];

        DB::table('usuarios')
            ->whereIn('correo', $correos)
            ->update(['password' => $hashCorrecto]);
    }

    public function down(): void
    {
        // No se revierte: no queremos volver a dejar contraseñas rotas.
    }
};
