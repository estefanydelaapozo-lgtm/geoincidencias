<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\AprobacionAutomaticaService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('supervisor:crear-demo', function () {
    $rol = Rol::where('slug', 'supervisor')->first();
    if (!$rol) {
        $this->error('No existe el rol "supervisor" en la tabla roles. Verifica que las migraciones se hayan ejecutado (php artisan migrate).');
        return 1;
    }
    Usuario::updateOrCreate(
        ['correo' => 'supervisor@geoincidencias.com'],
        [
            'nombre' => 'Supervisor',
            'apellido' => 'Demo',
            'password' => Hash::make('123456'),
            'rol' => 'supervisor',
            'id_rol' => $rol->id_rol,
            'activo' => true,
        ]
    );
    $this->info('Listo. Cuenta de supervisor creada/actualizada:');
    $this->line('  Correo:      supervisor@geoincidencias.com');
    $this->line('  Contraseña:  123456');
})->purpose('Crea o resetea una cuenta de Supervisor con credenciales de prueba conocidas');

Artisan::command('admin:crear-demo', function () {
    // Solo crea la cuenta de emergencia si el sistema no tiene NINGÚN
    // administrador todavía (base de datos nueva/vacía). Si ya existe un
    // admin (aunque no sea este correo), no toca nada para no resetear
    // contraseñas que el equipo ya haya cambiado.
    if (Usuario::where('rol', 'admin')->exists()) {
        $this->info('Ya existe al menos un administrador; no se modifica nada.');
        return 0;
    }
    Usuario::updateOrCreate(
        ['correo' => 'admin@geoincidencias.com'],
        [
            'nombre' => 'Admin',
            'apellido' => 'Sistema',
            'password' => Hash::make('123456'),
            'rol' => 'admin',
            'activo' => true,
        ]
    );
    $this->info('Listo. Cuenta de administrador creada:');
    $this->line('  Correo:      admin@geoincidencias.com');
    $this->line('  Contraseña:  123456');
})->purpose('Crea una cuenta de Administrador de emergencia solo si el sistema no tiene ninguno todavía');

Artisan::command('tecnico:crear-demo', function () {
    $rol = Rol::where('slug', 'tecnico')->first();
    if (!$rol) {
        $this->error('No existe el rol "tecnico" en la tabla roles. Verifica que las migraciones se hayan ejecutado (php artisan migrate).');
        return 1;
    }
    Usuario::updateOrCreate(
        ['correo' => 'tecnico@geoincidencias.com'],
        [
            'nombre' => 'Técnico',
            'apellido' => 'Demo',
            'password' => Hash::make('123456'),
            'rol' => 'tecnico',
            'id_rol' => $rol->id_rol,
            'activo' => true,
        ]
    );
    $this->info('Listo. Cuenta de técnico creada/actualizada:');
    $this->line('  Correo:      tecnico@geoincidencias.com');
    $this->line('  Contraseña:  123456');
})->purpose('Crea o resetea una cuenta de Técnico con credenciales de prueba conocidas');

Artisan::command('incidencias:auto-aprobar', function () {
    $total = AprobacionAutomaticaService::procesar();
    $this->info("Incidencias aprobadas automáticamente: {$total}");
})->purpose('Aprueba automáticamente las incidencias pendientes de revisión con más de 24 horas sin acción');
