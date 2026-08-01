<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id('id_rol');
            $table->string('slug', 50)->unique();
            $table->string('nombre', 100);
            $table->string('descripcion', 255)->nullable();
            $table->string('color', 20)->default('#64748b');
            $table->string('icono', 20)->default('◉');
            $table->boolean('es_institucional')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        DB::table('roles')->insert([
            ['slug'=>'admin','nombre'=>'Administrador','descripcion'=>'Control total del sistema','color'=>'#f59e0b','icono'=>'👑','es_institucional'=>0,'activo'=>1],
            ['slug'=>'usuario','nombre'=>'Ciudadano','descripcion'=>'Registra y consulta incidencias','color'=>'#64748b','icono'=>'👤','es_institucional'=>0,'activo'=>1],
            ['slug'=>'tecnico','nombre'=>'Técnico general','descripcion'=>'Atención técnica general','color'=>'#06b6d4','icono'=>'🔧','es_institucional'=>1,'activo'=>1],
            ['slug'=>'policia','nombre'=>'Policía','descripcion'=>'Seguridad, robos, violencia y accidentes','color'=>'#2563eb','icono'=>'👮','es_institucional'=>1,'activo'=>1],
            ['slug'=>'bomberos','nombre'=>'Bomberos','descripcion'=>'Incendios, rescates y fugas de gas','color'=>'#dc2626','icono'=>'🚒','es_institucional'=>1,'activo'=>1],
            ['slug'=>'salud','nombre'=>'Salud / Emergencias','descripcion'=>'Emergencias médicas y sanitarias','color'=>'#16a34a','icono'=>'🚑','es_institucional'=>1,'activo'=>1],
            ['slug'=>'electrica','nombre'=>'Empresa Eléctrica','descripcion'=>'Cortes, postes y alumbrado','color'=>'#eab308','icono'=>'⚡','es_institucional'=>1,'activo'=>1],
            ['slug'=>'agua','nombre'=>'Agua Potable','descripcion'=>'Fugas y servicio de agua','color'=>'#0ea5e9','icono'=>'🚰','es_institucional'=>1,'activo'=>1],
            ['slug'=>'obras_publicas','nombre'=>'Obras Públicas','descripcion'=>'Calles, baches y señalización','color'=>'#f97316','icono'=>'🛣️','es_institucional'=>1,'activo'=>1],
            ['slug'=>'medio_ambiente','nombre'=>'Medio Ambiente','descripcion'=>'Basura, contaminación y áreas verdes','color'=>'#22c55e','icono'=>'🌳','es_institucional'=>1,'activo'=>1],
            ['slug'=>'supervisor','nombre'=>'Supervisor','descripcion'=>'Supervisión y reportes institucionales','color'=>'#8b5cf6','icono'=>'🧭','es_institucional'=>1,'activo'=>1],
        ]);

        // El enum anterior impedía guardar los nuevos roles.
        DB::statement("ALTER TABLE usuarios MODIFY rol VARCHAR(50) NOT NULL DEFAULT 'usuario'");
        Schema::table('usuarios', function (Blueprint $table) {
            $table->unsignedBigInteger('id_rol')->nullable()->after('rol');
            $table->foreign('id_rol')->references('id_rol')->on('roles')->nullOnDelete();
            $table->index('id_rol');
        });
        DB::statement("UPDATE usuarios u JOIN roles r ON r.slug=u.rol SET u.id_rol=r.id_rol");

        Schema::table('tipos_incidencia', function (Blueprint $table) {
            $table->unsignedBigInteger('id_rol_responsable')->nullable()->after('color');
            $table->foreign('id_rol_responsable')->references('id_rol')->on('roles')->nullOnDelete();
        });

        // Asignación inicial por palabras clave. Se puede ajustar luego desde SQL.
        DB::statement("UPDATE tipos_incidencia t JOIN roles r ON r.slug='policia' SET t.id_rol_responsable=r.id_rol WHERE LOWER(t.nombre) REGEXP 'seguridad|robo|violencia|vandal|accidente'");
        DB::statement("UPDATE tipos_incidencia t JOIN roles r ON r.slug='bomberos' SET t.id_rol_responsable=r.id_rol WHERE LOWER(t.nombre) REGEXP 'incendio|rescate|gas|fuego'");
        DB::statement("UPDATE tipos_incidencia t JOIN roles r ON r.slug='salud' SET t.id_rol_responsable=r.id_rol WHERE LOWER(t.nombre) REGEXP 'salud|m[eé]dic|emergencia|sanitari'");
        DB::statement("UPDATE tipos_incidencia t JOIN roles r ON r.slug='electrica' SET t.id_rol_responsable=r.id_rol WHERE LOWER(t.nombre) REGEXP 'el[eé]ctric|energ[ií]a|alumbrado|poste'");
        DB::statement("UPDATE tipos_incidencia t JOIN roles r ON r.slug='agua' SET t.id_rol_responsable=r.id_rol WHERE LOWER(t.nombre) REGEXP 'agua|fuga|alcantarill'");
        DB::statement("UPDATE tipos_incidencia t JOIN roles r ON r.slug='obras_publicas' SET t.id_rol_responsable=r.id_rol WHERE LOWER(t.nombre) REGEXP 'infraestructura|bache|calle|vial|señal'");
        DB::statement("UPDATE tipos_incidencia t JOIN roles r ON r.slug='medio_ambiente' SET t.id_rol_responsable=r.id_rol WHERE LOWER(t.nombre) REGEXP 'ambiente|basura|contamin|árbol|arbol'");
    }

    public function down(): void
    {
        Schema::table('tipos_incidencia', fn(Blueprint $t) => $t->dropConstrainedForeignId('id_rol_responsable'));
        Schema::table('usuarios', fn(Blueprint $t) => $t->dropConstrainedForeignId('id_rol'));
        Schema::dropIfExists('roles');
    }
};
