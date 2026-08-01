<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// BUG CRÍTICO CORREGIDO: el hash bcrypt usado en las migraciones anteriores
// para crear las cuentas demo (supervisor, policía, bomberos, salud, eléctrica,
// agua, obras públicas, medio ambiente, técnico) fue copiado de un archivo
// viejo del proyecto asumiendo que correspondía a la contraseña "123456",
// pero en realidad NO verificaba correctamente contra esa contraseña. Por
// eso el login fallaba con "Credenciales incorrectas" aunque la cuenta
// existiera. Este hash nuevo SÍ fue verificado byte a byte contra "123456".
return new class extends Migration
{
    public function up(): void
    {
        // Hash verificado: corresponde exactamente a la contraseña "123456".
        $hashCorrecto = '$2y$10$kCCW6z2a5MGwcGQ/s9oWoO93JnGDDvjLyn.TBwyODy4nMYfAJ0F3S';

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
