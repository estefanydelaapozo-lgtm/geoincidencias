<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id('id_usuario');
            $table->string('nombre', 100);
            $table->string('apellido', 100)->nullable();
            $table->string('correo', 150)->unique();
            $table->string('password');
            $table->enum('rol', ['admin', 'tecnico', 'usuario'])->default('usuario');
            $table->string('telefono', 20)->nullable();
            $table->decimal('saldo_incentivos', 10, 2)->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
