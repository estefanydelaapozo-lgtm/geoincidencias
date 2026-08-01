<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subtipos_incidencia', function (Blueprint $table) {
            $table->id('id_subtipo');
            $table->foreignId('id_tipo')->constrained('tipos_incidencia', 'id_tipo');
            $table->string('nombre', 100);
            $table->boolean('activo')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subtipos_incidencia');
    }
};
