<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration {
    public function up(): void
    {
        // 1) Reparar usuarios cuyo id_rol no coincide con su rol de texto
        //    (pasa con usuarios creados por scripts SQL manuales que no
        //    pasaron por el formulario de la app).
        DB::statement("
            UPDATE usuarios u
            JOIN roles r ON r.slug = u.rol
            SET u.id_rol = r.id_rol
            WHERE u.id_rol IS NULL OR u.id_rol <> r.id_rol
        ");

        // 2) Asignar responsable institucional a tipos de incidencia que
        //    hayan quedado sin institución (misma lógica de palabras clave
        //    de la migración original de roles institucionales).
        $reglas = [
            'policia'        => 'seguridad|robo|violencia|vandal|accidente',
            'bomberos'       => 'incendio|rescate|gas|fuego',
            'salud'          => 'salud|m[eé]dic|emergencia|sanitari',
            'electrica'      => 'el[eé]ctric|energ[ií]a|alumbrado|poste',
            'agua'           => 'agua|fuga|alcantarill',
            'obras_publicas' => 'infraestructura|bache|calle|vial|se[ñn]al',
            'medio_ambiente' => 'ambiente|basura|contamin|[aá]rbol',
        ];
        foreach ($reglas as $slug => $regex) {
            DB::statement("
                UPDATE tipos_incidencia t JOIN roles r ON r.slug = ?
                SET t.id_rol_responsable = r.id_rol
                WHERE t.id_rol_responsable IS NULL
                  AND LOWER(t.nombre) REGEXP ?
            ", [$slug, $regex]);
        }

        // 3) Rellenar coordenadas de incidencias que se registraron sin
        //    latitud/longitud, usando la referencia de su zona, para que
        //    no desaparezcan del mapa.
        DB::statement("
            UPDATE incidencias i
            JOIN zonas z ON i.id_zona = z.id_zona
            SET i.latitud = z.latitud_ref, i.longitud = z.longitud_ref
            WHERE (i.latitud IS NULL OR i.longitud IS NULL)
              AND z.latitud_ref IS NOT NULL AND z.longitud_ref IS NOT NULL
        ");

        // 4) Crear (o dejar lista) una cuenta de prueba por cada rol
        //    institucional, con id_rol siempre bien enlazado.
        //    Contraseña para todas: 123456
        $hash = Hash::make('123456');
        $usuariosDemo = [
            ['Supervisor', 'supervisor@geoincidencias.com', 'supervisor'],
            ['Policía', 'policia@geoincidencias.com', 'policia'],
            ['Bomberos', 'bomberos@geoincidencias.com', 'bomberos'],
            ['Salud', 'salud@geoincidencias.com', 'salud'],
            ['Eléctrica', 'electrica@geoincidencias.com', 'electrica'],
            ['Agua Potable', 'agua@geoincidencias.com', 'agua'],
            ['Obras Públicas', 'obras@geoincidencias.com', 'obras_publicas'],
            ['Medio Ambiente', 'ambiente@geoincidencias.com', 'medio_ambiente'],
            ['Técnico', 'tecnico@geoincidencias.com', 'tecnico'],
        ];
        foreach ($usuariosDemo as [$nombre, $correo, $slug]) {
            $idRol = DB::table('roles')->where('slug', $slug)->value('id_rol');
            if (!$idRol) continue;
            DB::table('usuarios')->updateOrInsert(
                ['correo' => $correo],
                [
                    'nombre' => $nombre, 'apellido' => 'Demo', 'password' => $hash,
                    'rol' => $slug, 'id_rol' => $idRol, 'activo' => 1,
                    'created_at' => now(), 'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        // Ajuste de datos, no reversible de forma segura.
    }
};
