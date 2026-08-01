<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Limpiar datos existentes solo si la tabla usuarios está vacía ──
        $usuariosCount = DB::table('usuarios')->count();
        
        if ($usuariosCount === 0) {
            // Solo limpiar si no hay usuarios
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('historial_actividad')->truncate();
            DB::table('notificaciones')->truncate();
            DB::table('incidencia_comentarios')->truncate();
            DB::table('incidencia_estados_historial')->truncate();
            DB::table('incidencia_apoyos')->truncate();
            DB::table('incidencia_asignaciones')->truncate();
            DB::table('incidencias')->truncate();
            DB::table('personal_access_tokens')->truncate();
            DB::table('usuarios')->truncate();
            DB::table('subtipos_incidencia')->truncate();
            DB::table('tipos_incidencia')->truncate();
            DB::table('estados')->truncate();
            DB::table('incentivos_prioridad')->truncate();
            DB::table('zonas')->truncate();
            DB::table('ciudades')->truncate();
            DB::table('provincias')->truncate();
            DB::table('paises')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        // ── Ubicación ──
        DB::table('paises')->updateOrInsert(['id_pais' => 1], ['id_pais' => 1, 'nombre' => 'Ecuador', 'codigo_iso' => 'EC']);

        DB::table('provincias')->updateOrInsert(['id_provincia' => 1], ['id_provincia' => 1, 'id_pais' => 1, 'nombre' => 'Guayas']);
        DB::table('provincias')->updateOrInsert(['id_provincia' => 2], ['id_provincia' => 2, 'id_pais' => 1, 'nombre' => 'Pichincha']);
        DB::table('provincias')->updateOrInsert(['id_provincia' => 3], ['id_provincia' => 3, 'id_pais' => 1, 'nombre' => 'Santa Elena']);

        DB::table('ciudades')->updateOrInsert(['id_ciudad' => 1], ['id_ciudad' => 1, 'id_provincia' => 1, 'nombre' => 'Guayaquil', 'latitud_ref' => -2.170998, 'longitud_ref' => -79.922359]);
        DB::table('ciudades')->updateOrInsert(['id_ciudad' => 2], ['id_ciudad' => 2, 'id_provincia' => 2, 'nombre' => 'Quito', 'latitud_ref' => -0.180653, 'longitud_ref' => -78.467838]);
        DB::table('ciudades')->updateOrInsert(['id_ciudad' => 3], ['id_ciudad' => 3, 'id_provincia' => 3, 'nombre' => 'La Libertad', 'latitud_ref' => -2.232450, 'longitud_ref' => -80.905610]);

        // ── Zonas (todas apuntan a Guayaquil - id_ciudad = 1) ──
        DB::table('zonas')->updateOrInsert(['id_zona' => 1], ['id_zona' => 1, 'id_ciudad' => 1, 'nombre' => 'Planta Baja', 'descripcion' => 'Area de recepcion y acceso principal', 'latitud_ref' => -2.900100, 'longitud_ref' => -79.005900, 'activo' => 1]);
        DB::table('zonas')->updateOrInsert(['id_zona' => 2], ['id_zona' => 2, 'id_ciudad' => 1, 'nombre' => 'Piso 1', 'descripcion' => 'Oficinas administrativas', 'latitud_ref' => -2.900200, 'longitud_ref' => -79.005800, 'activo' => 1]);
        DB::table('zonas')->updateOrInsert(['id_zona' => 3], ['id_zona' => 3, 'id_ciudad' => 1, 'nombre' => 'Piso 2', 'descripcion' => 'Area tecnica y sistemas', 'latitud_ref' => -2.900300, 'longitud_ref' => -79.005700, 'activo' => 1]);
        DB::table('zonas')->updateOrInsert(['id_zona' => 4], ['id_zona' => 4, 'id_ciudad' => 1, 'nombre' => 'Piso 3', 'descripcion' => 'Gerencia y salas de reuniones', 'latitud_ref' => -2.900400, 'longitud_ref' => -79.005600, 'activo' => 1]);
        DB::table('zonas')->updateOrInsert(['id_zona' => 5], ['id_zona' => 5, 'id_ciudad' => 1, 'nombre' => 'Bodega', 'descripcion' => 'Almacen y logistica', 'latitud_ref' => -2.900500, 'longitud_ref' => -79.005500, 'activo' => 1]);
        DB::table('zonas')->updateOrInsert(['id_zona' => 6], ['id_zona' => 6, 'id_ciudad' => 1, 'nombre' => 'Parqueadero', 'descripcion' => 'Zona de parqueo vehicular', 'latitud_ref' => -2.900600, 'longitud_ref' => -79.005400, 'activo' => 1]);
        DB::table('zonas')->updateOrInsert(['id_zona' => 7], ['id_zona' => 7, 'id_ciudad' => 1, 'nombre' => 'Sala de Servidores', 'descripcion' => 'Centro de datos principal', 'latitud_ref' => -2.900700, 'longitud_ref' => -79.005300, 'activo' => 1]);
        DB::table('zonas')->updateOrInsert(['id_zona' => 8], ['id_zona' => 8, 'id_ciudad' => 1, 'nombre' => 'Cafeteria', 'descripcion' => 'Area de descanso y comedor', 'latitud_ref' => -2.900800, 'longitud_ref' => -79.005200, 'activo' => 1]);

        DB::table('tipos_incidencia')->updateOrInsert(['id_tipo' => 1], ['id_tipo' => 1, 'nombre' => 'Infraestructura', 'descripcion' => 'Danos en instalaciones fisicas', 'icono' => 'bi-building', 'color' => '#f97316', 'activo' => 1]);
        DB::table('tipos_incidencia')->updateOrInsert(['id_tipo' => 2], ['id_tipo' => 2, 'nombre' => 'Equipos TI', 'descripcion' => 'Fallas en hardware o software', 'icono' => 'bi-pc-display', 'color' => '#6366f1', 'activo' => 1]);
        DB::table('tipos_incidencia')->updateOrInsert(['id_tipo' => 3], ['id_tipo' => 3, 'nombre' => 'Red y Conectividad', 'descripcion' => 'Problemas de red, internet o telefonia', 'icono' => 'bi-wifi-off', 'color' => '#3b82f6', 'activo' => 1]);
        DB::table('tipos_incidencia')->updateOrInsert(['id_tipo' => 4], ['id_tipo' => 4, 'nombre' => 'Seguridad', 'descripcion' => 'Incidentes de seguridad fisica o digital', 'icono' => 'bi-shield-exclamation', 'color' => '#ef4444', 'activo' => 1]);
        DB::table('tipos_incidencia')->updateOrInsert(['id_tipo' => 5], ['id_tipo' => 5, 'nombre' => 'Suministros', 'descripcion' => 'Falta o dano de materiales', 'icono' => 'bi-box-seam', 'color' => '#eab308', 'activo' => 1]);
        DB::table('tipos_incidencia')->updateOrInsert(['id_tipo' => 6], ['id_tipo' => 6, 'nombre' => 'Servicios Basicos', 'descripcion' => 'Agua, luz, clima, aseo', 'icono' => 'bi-lightning-charge', 'color' => '#10b981', 'activo' => 1]);
        DB::table('tipos_incidencia')->updateOrInsert(['id_tipo' => 7], ['id_tipo' => 7, 'nombre' => 'Accidentes', 'descripcion' => 'Accidentes laborales o de transito', 'icono' => 'bi-bandaid', 'color' => '#f43f5e', 'activo' => 1]);

        DB::table('subtipos_incidencia')->updateOrInsert(['id_subtipo' => 1], ['id_subtipo' => 1, 'id_tipo' => 1, 'nombre' => 'Alumbrado', 'activo' => 1]);
        DB::table('subtipos_incidencia')->updateOrInsert(['id_subtipo' => 2], ['id_subtipo' => 2, 'id_tipo' => 1, 'nombre' => 'Goteras y Filtraciones', 'activo' => 1]);
        DB::table('subtipos_incidencia')->updateOrInsert(['id_subtipo' => 3], ['id_subtipo' => 3, 'id_tipo' => 1, 'nombre' => 'Puertas y accesos', 'activo' => 1]);
        DB::table('subtipos_incidencia')->updateOrInsert(['id_subtipo' => 4], ['id_subtipo' => 4, 'id_tipo' => 1, 'nombre' => 'Mobiliario danado', 'activo' => 1]);
        DB::table('subtipos_incidencia')->updateOrInsert(['id_subtipo' => 5], ['id_subtipo' => 5, 'id_tipo' => 2, 'nombre' => 'Computador no enciende', 'activo' => 1]);
        DB::table('subtipos_incidencia')->updateOrInsert(['id_subtipo' => 6], ['id_subtipo' => 6, 'id_tipo' => 2, 'nombre' => 'Error de software', 'activo' => 1]);
        DB::table('subtipos_incidencia')->updateOrInsert(['id_subtipo' => 7], ['id_subtipo' => 7, 'id_tipo' => 2, 'nombre' => 'Impresora', 'activo' => 1]);
        DB::table('subtipos_incidencia')->updateOrInsert(['id_subtipo' => 8], ['id_subtipo' => 8, 'id_tipo' => 2, 'nombre' => 'Perdida de datos', 'activo' => 1]);
        DB::table('subtipos_incidencia')->updateOrInsert(['id_subtipo' => 9], ['id_subtipo' => 9, 'id_tipo' => 3, 'nombre' => 'Internet lento', 'activo' => 1]);
        DB::table('subtipos_incidencia')->updateOrInsert(['id_subtipo' => 10], ['id_subtipo' => 10, 'id_tipo' => 3, 'nombre' => 'Sin conexion', 'activo' => 1]);
        DB::table('subtipos_incidencia')->updateOrInsert(['id_subtipo' => 11], ['id_subtipo' => 11, 'id_tipo' => 3, 'nombre' => 'Telefonia IP', 'activo' => 1]);
        DB::table('subtipos_incidencia')->updateOrInsert(['id_subtipo' => 12], ['id_subtipo' => 12, 'id_tipo' => 4, 'nombre' => 'Robo', 'activo' => 1]);
        DB::table('subtipos_incidencia')->updateOrInsert(['id_subtipo' => 13], ['id_subtipo' => 13, 'id_tipo' => 4, 'nombre' => 'Acceso no autorizado', 'activo' => 1]);
        DB::table('subtipos_incidencia')->updateOrInsert(['id_subtipo' => 14], ['id_subtipo' => 14, 'id_tipo' => 4, 'nombre' => 'Camara danada', 'activo' => 1]);
        DB::table('subtipos_incidencia')->updateOrInsert(['id_subtipo' => 15], ['id_subtipo' => 15, 'id_tipo' => 4, 'nombre' => 'Alarma activada', 'activo' => 1]);
        DB::table('subtipos_incidencia')->updateOrInsert(['id_subtipo' => 16], ['id_subtipo' => 16, 'id_tipo' => 5, 'nombre' => 'Falta de insumos de oficina', 'activo' => 1]);
        DB::table('subtipos_incidencia')->updateOrInsert(['id_subtipo' => 17], ['id_subtipo' => 17, 'id_tipo' => 5, 'nombre' => 'Falta de equipo de proteccion', 'activo' => 1]);
        DB::table('subtipos_incidencia')->updateOrInsert(['id_subtipo' => 18], ['id_subtipo' => 18, 'id_tipo' => 6, 'nombre' => 'Corte de energia', 'activo' => 1]);
        DB::table('subtipos_incidencia')->updateOrInsert(['id_subtipo' => 19], ['id_subtipo' => 19, 'id_tipo' => 6, 'nombre' => 'Falla de climatizacion', 'activo' => 1]);
        DB::table('subtipos_incidencia')->updateOrInsert(['id_subtipo' => 20], ['id_subtipo' => 20, 'id_tipo' => 6, 'nombre' => 'Falta de agua', 'activo' => 1]);
        DB::table('subtipos_incidencia')->updateOrInsert(['id_subtipo' => 21], ['id_subtipo' => 21, 'id_tipo' => 7, 'nombre' => 'Accidente laboral', 'activo' => 1]);
        DB::table('subtipos_incidencia')->updateOrInsert(['id_subtipo' => 22], ['id_subtipo' => 22, 'id_tipo' => 7, 'nombre' => 'Accidente vehicular', 'activo' => 1]);

        DB::table('estados')->updateOrInsert(['id_estado' => 1], ['id_estado' => 1, 'nombre' => 'Registrada', 'descripcion' => 'Incidencia reportada, aun no atendida', 'color' => '#ef4444', 'orden' => 1, 'activo' => 1]);
        DB::table('estados')->updateOrInsert(['id_estado' => 2], ['id_estado' => 2, 'nombre' => 'En proceso', 'descripcion' => 'Incidencia siendo atendida por el responsable', 'color' => '#f59e0b', 'orden' => 2, 'activo' => 1]);
        DB::table('estados')->updateOrInsert(['id_estado' => 3], ['id_estado' => 3, 'nombre' => 'Resuelta', 'descripcion' => 'Incidencia solucionada', 'color' => '#22c55e', 'orden' => 3, 'activo' => 1]);
        DB::table('estados')->updateOrInsert(['id_estado' => 4], ['id_estado' => 4, 'nombre' => 'Cerrada', 'descripcion' => 'Incidencia verificada y cerrada oficialmente', 'color' => '#64748b', 'orden' => 4, 'activo' => 1]);

        DB::table('incentivos_prioridad')->updateOrInsert(['prioridad' => 'Baja'], ['prioridad' => 'Baja', 'monto' => 5.00]);
        DB::table('incentivos_prioridad')->updateOrInsert(['prioridad' => 'Media'], ['prioridad' => 'Media', 'monto' => 10.00]);
        DB::table('incentivos_prioridad')->updateOrInsert(['prioridad' => 'Alta'], ['prioridad' => 'Alta', 'monto' => 20.00]);

        $hash = Hash::make('123456');
        // No se fuerza id_usuario: si el usuario ya existe con otro ID (por ejemplo,
        // porque ya se habían creado cuentas antes), esto solo actualiza sus datos
        // en vez de intentar insertar con un ID que ya está ocupado por otra cuenta.
        DB::table('usuarios')->updateOrInsert(['correo' => 'admin@geoincidencias.com'], ['nombre' => 'Admin', 'apellido' => 'Sistema', 'password' => $hash, 'rol' => 'admin', 'telefono' => '0990000000', 'saldo_incentivos' => 0.00, 'activo' => 1, 'created_at' => now()]);
        DB::table('usuarios')->updateOrInsert(['correo' => 'cmendoza@empresa.com'], ['nombre' => 'Carlos', 'apellido' => 'Mendoza', 'password' => $hash, 'rol' => 'tecnico', 'telefono' => '0991234567', 'saldo_incentivos' => 20.00, 'activo' => 1, 'created_at' => now()]);
        DB::table('usuarios')->updateOrInsert(['correo' => 'mgonzalez@empresa.com'], ['nombre' => 'Maria', 'apellido' => 'Gonzalez', 'password' => $hash, 'rol' => 'usuario', 'telefono' => '0992345678', 'saldo_incentivos' => 0.00, 'activo' => 1, 'created_at' => now()]);

        // IDs reales (pueden no ser 1,2,3 si ya existían otras cuentas antes).
        $idAdmin = DB::table('usuarios')->where('correo', 'admin@geoincidencias.com')->value('id_usuario');
        $idTecnico = DB::table('usuarios')->where('correo', 'cmendoza@empresa.com')->value('id_usuario');
        $idCiudadano = DB::table('usuarios')->where('correo', 'mgonzalez@empresa.com')->value('id_usuario');

        // Incidencias de ejemplo (solo si la tabla está vacía y los 3 usuarios de arriba existen).
        if ($idAdmin && $idTecnico && $idCiudadano && DB::table('incidencias')->count() === 0) {
            try {
                $base = ['estado_aprobacion' => 'aprobada', 'id_admin_revisor' => $idAdmin, 'fecha_revision' => now()];
                $ejemplos = [
                    ['titulo'=>'Falla en servidor principal','descripcion'=>'El servidor de base de datos no responde desde las 08:00.','prioridad'=>'Alta','id_tipo'=>2,'id_subtipo'=>5,'id_estado_actual'=>2,'id_zona'=>7,'latitud'=>-2.900700,'longitud'=>-79.005300,'fecha_ocurrencia'=>'2026-06-15','hora_ocurrencia'=>'08:15','reportante_nombre'=>'Ana Suarez','reportante_contacto'=>'0997001122','id_usuario_creador'=>$idTecnico],
                    ['titulo'=>'Corte de energia en piso 2','descripcion'=>'Se fue la luz en el ala norte del piso 2.','prioridad'=>'Alta','id_tipo'=>6,'id_subtipo'=>18,'id_estado_actual'=>2,'id_zona'=>1,'latitud'=>-2.900200,'longitud'=>-79.005800,'fecha_ocurrencia'=>'2026-06-15','hora_ocurrencia'=>'09:30','reportante_nombre'=>'Luis Paredes','reportante_contacto'=>'0997002233','id_usuario_creador'=>$idCiudadano],
                    ['titulo'=>'Filtracion de agua en techo de bodega','descripcion'=>'Se detecto humedad y goteo en el techo de la bodega sector B.','prioridad'=>'Alta','id_tipo'=>1,'id_subtipo'=>2,'id_estado_actual'=>1,'id_zona'=>5,'latitud'=>-2.900500,'longitud'=>-79.005500,'fecha_ocurrencia'=>'2026-06-14','hora_ocurrencia'=>'14:00','reportante_nombre'=>'Roberto Mora','reportante_contacto'=>'0997003344','id_usuario_creador'=>$idAdmin],
                    ['titulo'=>'Impresora de recepcion fuera de servicio','descripcion'=>'La impresora no imprime y muestra error de papel atascado.','prioridad'=>'Media','id_tipo'=>2,'id_subtipo'=>7,'id_estado_actual'=>3,'id_zona'=>1,'latitud'=>-2.900100,'longitud'=>-79.005900,'fecha_ocurrencia'=>'2026-06-16','hora_ocurrencia'=>'10:00','fecha_resolucion'=>now(),'tiempo_resolucion_horas'=>2.5,'reportante_nombre'=>'Sofia Chavez','reportante_contacto'=>'0997004455','id_usuario_creador'=>$idTecnico],
                    ['titulo'=>'Internet muy lento en piso 3','descripcion'=>'La velocidad de internet es inferior a 1 Mbps en el piso 3.','prioridad'=>'Media','id_tipo'=>3,'id_subtipo'=>9,'id_estado_actual'=>2,'id_zona'=>3,'latitud'=>-2.900400,'longitud'=>-79.005600,'fecha_ocurrencia'=>'2026-06-16','hora_ocurrencia'=>'11:30','reportante_nombre'=>'Jorge Ruiz','reportante_contacto'=>'0997005566','id_usuario_creador'=>$idCiudadano],
                    ['titulo'=>'Falta de papel en impresoras','descripcion'=>'No hay papel en ninguna de las impresoras del piso 1.','prioridad'=>'Baja','id_tipo'=>5,'id_subtipo'=>16,'id_estado_actual'=>3,'id_zona'=>2,'latitud'=>-2.900200,'longitud'=>-79.005800,'fecha_ocurrencia'=>'2026-06-17','hora_ocurrencia'=>'09:00','fecha_resolucion'=>now(),'tiempo_resolucion_horas'=>1.0,'reportante_nombre'=>'Elena Vega','reportante_contacto'=>'0997006677','id_usuario_creador'=>$idAdmin],
                    ['titulo'=>'Alarma de seguridad activada','descripcion'=>'La alarma de seguridad se activo sin motivo aparente.','prioridad'=>'Alta','id_tipo'=>4,'id_subtipo'=>15,'id_estado_actual'=>3,'id_zona'=>1,'latitud'=>-2.900100,'longitud'=>-79.005900,'fecha_ocurrencia'=>'2026-06-17','hora_ocurrencia'=>'15:45','fecha_resolucion'=>now(),'tiempo_resolucion_horas'=>0.5,'reportante_nombre'=>'Guardia Nocturno','reportante_contacto'=>'0997007788','id_usuario_creador'=>$idTecnico],
                    ['titulo'=>'Falla de aire acondicionado en sala servidores','descripcion'=>'La temperatura en la sala de servidores esta subiendo a 28°C.','prioridad'=>'Alta','id_tipo'=>6,'id_subtipo'=>19,'id_estado_actual'=>2,'id_zona'=>7,'latitud'=>-2.900700,'longitud'=>-79.005300,'fecha_ocurrencia'=>'2026-06-18','hora_ocurrencia'=>'07:00','reportante_nombre'=>'Tecnico Datacenter','reportante_contacto'=>'0997008899','id_usuario_creador'=>$idCiudadano],
                ];
                foreach ($ejemplos as $fila) {
                    DB::table('incidencias')->insert($fila + $base);
                }
            } catch (\Exception $e) {
                // Si falla la inserción de incidencias, continuar sin ellas
            }
        }
    }
}
