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
        Schema::create('boleta_pagos', function (Blueprint $table) {
            $table->id(); // Sustituye a NoPago

            // Relaciones
            $table->foreignId('boleta_id')->constrained('boletas'); // Sustituye a NoServicio
            $table->foreignId('user_id')->constrained('users'); // Sustituye a NoUsuario
            $table->integer('caja_id')->nullable();

            // Datos del Ticket / Transacción
            $table->string('referencia', 40)->nullable();
            $table->string('autorizacion', 40)->nullable();

            // Importes (10 dígitos en total, 2 decimales = Máximo 99 millones)
            $table->decimal('importe', 10, 2)->default(0);
            $table->decimal('comision', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->decimal('importe_recibido', 10, 2)->default(0);
            $table->decimal('cambio', 10, 2)->default(0);

            // Control de Estatus y Cancelaciones
            $table->string('estatus', 2)->default('A')->comment('A=Activo, C=Cancelado');
            $table->foreignId('usuario_cancelacion_id')->nullable()->constrained('users');
            $table->string('motivo_cancelacion', 100)->nullable();
            $table->dateTime('fecha_cancelacion')->nullable();

            // Reemplaza a FechaAlta, FechaPago y HoraPago automáticamente
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boleta_pagos');
    }
};
