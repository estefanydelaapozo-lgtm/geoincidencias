<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            if (!Schema::hasColumn('usuarios', 'foto_perfil')) {
                $table->string('foto_perfil', 255)->nullable()->after('telefono');
            }
        });
        Schema::create('security_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('evento', 100)->index();
            $table->foreignId('id_usuario')->nullable()->constrained('usuarios', 'id_usuario')->nullOnDelete();
            $table->string('ip_origen', 45)->nullable()->index();
            $table->string('metodo', 10)->nullable();
            $table->string('ruta', 255)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->json('contexto')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_audit_logs');
        Schema::table('usuarios', fn (Blueprint $table) => $table->dropColumn('foto_perfil'));
    }
};
