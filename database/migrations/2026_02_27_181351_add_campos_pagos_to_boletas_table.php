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
        Schema::table('boletas', function (Blueprint $table) {
            Schema::table('boletas', function (Blueprint $table) {
                // Campos que pueden ser nulos porque las boletas TRADICIONALES no los usan
                $table->integer('periodo_id')->nullable()->comment('1:Sem, 2:Cator, 3:Quin, 4:Men');
                $table->integer('numero_pagos')->nullable();
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('boletas', function (Blueprint $table) {
            $table->dropColumn(['periodo_id', 'numero_pagos', 'pago_fijo', 'ultimo_pago']);
        });
    }
};
