<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    if (!Schema::hasTable('paises')) {
        Schema::create('paises', function (Blueprint $table) {
            $table->id('id_pais');
            $table->string('nombre', 100);
            $table->string('codigo_iso', 2)->nullable();
        });
    }
}


    public function down(): void
    {
        Schema::dropIfExists('paises');
    }
};
