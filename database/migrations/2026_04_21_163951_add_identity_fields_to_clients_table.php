<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('apellido_paterno')->after('nombre')->nullable();
            $table->string('apellido_materno')->after('apellido_paterno')->nullable();
            $table->string('rfc', 13)->after('apellido_materno')->unique()->nullable();
            $table->string('estado_origen')->after('rfc')->nullable();
            $table->date('fecha_nacimiento')->after('estado_origen')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('apellido_paterno', 'apellido_materno', 'rfc', 'estado_origen', 'fecha_nacimiento');
        });
    }
};
