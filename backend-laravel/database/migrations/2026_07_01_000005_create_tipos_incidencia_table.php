<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_incidencia', function (Blueprint $table) {
            $table->id('id_tipo');
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->string('icono', 50)->nullable();
            $table->string('color', 7)->nullable();
            $table->boolean('activo')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_incidencia');
    }
};
