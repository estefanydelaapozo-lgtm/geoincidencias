<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        DB::statement("ALTER TABLE usuarios MODIFY rol ENUM('admin','tecnico','usuario') NOT NULL DEFAULT 'usuario'");
        DB::table('estados')->where('nombre','Pendiente')->update(['nombre'=>'Registrada','descripcion'=>'Incidencia registrada y pendiente de atención']);
        DB::table('estados')->where('nombre','Resuelto')->update(['nombre'=>'Resuelta']);
        DB::table('estados')->where('nombre','Cerrado')->update(['nombre'=>'Cerrada']);
        // Los índices ya se incluyen en las migraciones base para instalaciones nuevas.
        // Para bases existentes, ejecutar database/patches/u4_ape01_existing_database.sql.
    }
    public function down(): void {
        // No se revierten nombres para evitar invalidar historial existente.
    }
};
