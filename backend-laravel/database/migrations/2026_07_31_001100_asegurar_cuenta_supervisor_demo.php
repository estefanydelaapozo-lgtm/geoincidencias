<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

// La migración 2026_07_31_001000 ya se ejecutó una vez sin incluir al
// supervisor en la lista de cuentas demo, así que esa cuenta nunca se creó.
// Como las migraciones no se repiten solas, esta migración nueva crea (o
// repara) esa cuenta específicamente. Aditiva y segura de re-ejecutar.
return new class extends Migration
{
    public function up(): void
    {
        $idRol = DB::table('roles')->where('slug', 'supervisor')->value('id_rol');
        $hash = Hash::make('123456');

        DB::table('usuarios')->updateOrInsert(
            ['correo' => 'supervisor@geoincidencias.com'],
            [
                'nombre' => 'Supervisor', 'apellido' => 'Demo', 'password' => $hash,
                'rol' => 'supervisor', 'id_rol' => $idRol, 'activo' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        // No se elimina la cuenta para no romper accesos ya en uso.
    }
};
