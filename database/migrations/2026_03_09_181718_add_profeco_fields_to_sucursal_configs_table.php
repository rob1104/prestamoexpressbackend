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
            $table->date('adhesion_fecha')->nullable();
            $table->string('adhesion_num')->nullable();
            $table->string('email')->nullable();
            $table->string('horario_atencion')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sucursal_configs', function (Blueprint $table) {
            $table->dropColumn(['adhesion_fecha', 'adhesion_num', 'email', 'horario_atencion']);
        });
    }
};
