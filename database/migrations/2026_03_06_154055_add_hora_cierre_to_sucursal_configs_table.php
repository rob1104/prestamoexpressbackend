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
        Schema::table('sucursal_configs', function (Blueprint $table) {
            $table->time('hora_cierre')->default('18:00:00')->after('nombre_sucursal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sucursal_configs', function (Blueprint $table) {
            $table->dropColumn('hora_cierre');
        });
    }
};
