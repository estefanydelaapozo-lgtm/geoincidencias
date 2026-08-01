<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ciudades', function (Blueprint $table) {
            $table->id('id_ciudad');
            $table->foreignId('id_provincia')->constrained('provincias', 'id_provincia');
            $table->string('nombre', 100);
            $table->decimal('latitud_ref', 10, 6)->nullable();
            $table->decimal('longitud_ref', 10, 6)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ciudades');
    }
};
