<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidencias', function (Blueprint $table) {
            if (!Schema::hasColumn('incidencias', 'foto')) {
                $table->string('foto', 255)->nullable()->after('direccion_texto');
            }
        });
    }

    public function down(): void
    {
        Schema::table('incidencias', function (Blueprint $table) {
            if (Schema::hasColumn('incidencias', 'foto')) {
                $table->dropColumn('foto');
            }
        });
    }
};
