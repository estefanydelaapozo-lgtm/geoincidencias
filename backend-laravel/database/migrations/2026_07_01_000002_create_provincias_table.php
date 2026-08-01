<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provincias', function (Blueprint $table) {
            $table->id('id_provincia');
            $table->foreignId('id_pais')->constrained('paises', 'id_pais');
            $table->string('nombre', 100);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provincias');
    }
};
