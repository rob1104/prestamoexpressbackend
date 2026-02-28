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
        Schema::create('calendario_pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boleta_id')->constrained('boletas')->onDelete('cascade');
            $table->integer('num_pago');
            $table->date('fecha_vencimiento');
            $table->decimal('monto', 10, 2);
            $table->string('estatus', 20)->default('PE'); // PE=Pendiente, PA=Pagado, VE=Vencido
            $table->date('fecha_pago')->nullable(); // Cuándo vino el cliente a pagar esa letra
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendario_pagos');
    }
};
