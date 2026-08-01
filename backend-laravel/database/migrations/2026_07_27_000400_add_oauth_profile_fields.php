<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('usuarios', function (Blueprint $table) {
            if (!Schema::hasColumn('usuarios', 'google_id')) $table->string('google_id', 191)->nullable()->unique()->after('correo');
            if (!Schema::hasColumn('usuarios', 'auth_provider')) $table->string('auth_provider', 30)->default('local')->after('google_id');
            if (!Schema::hasColumn('usuarios', 'email_verified_at')) $table->timestamp('email_verified_at')->nullable()->after('auth_provider');
            if (!Schema::hasColumn('usuarios', 'remember_token')) $table->rememberToken();
        });
    }
    public function down(): void {
        Schema::table('usuarios', function (Blueprint $table) {
            foreach (['google_id','auth_provider','email_verified_at','remember_token'] as $column) {
                if (Schema::hasColumn('usuarios', $column)) $table->dropColumn($column);
            }
        });
    }
};
