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
        Schema::table('boleta_pagos', function (Blueprint $table) {
            $table->dateTime('fecha_pago')->nullable();
            $table->dateTime('fecha_vencimiento')->nullable();
            $table->integer('num_pago')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('boleta_pagos', function (Blueprint $table) {
            $table->dropColumn('fecha_pago', 'num_pago');
        });
    }
};
