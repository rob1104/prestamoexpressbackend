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
        Schema::create('boleta_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boleta_id')->constrained('boletas')->cascadeOnDelete();
            $table->foreignId('boleta_vencimiento_id')->nullable()->constrained('boleta_vencimientos')->nullOnDelete();

            $table->enum('tipo', [
                'refrendo',
                'recuperacion',
                'liquidacion',
                'adjudicacion',
                'cancelacion',
                'empeño'
            ]);

            $table->decimal('capital_original', 12, 2)->default(0);
            $table->decimal('comision_original', 12, 2)->default(0);
            $table->decimal('capital_aplicado', 12, 2)->default(0);
            $table->decimal('comision_proporcional', 12, 2)->default(0);
            $table->decimal('recargos', 12, 2)->default(0);
            $table->decimal('penalizacion', 12, 2)->default(0);
            $table->decimal('importe_pagado', 12, 2)->default(0);
            $table->decimal('importe_recibido', 12, 2)->default(0);
            $table->decimal('abono_capital', 12, 2)->default(0);

            // Boleta nueva generada por el movimiento (refrendo, cambiate, etc.)
            $table->unsignedBigInteger('boleta_nueva_id')->nullable();
            $table->foreign('boleta_nueva_id')->references('id')->on('boletas')->nullOnDelete();

            $table->enum('estatus', ['aplicado', 'cancelado'])->default('aplicado');
            $table->string('motivo_cancelacion', 120)->nullable();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('usuario_cancelacion_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('fecha_movimiento');
            $table->timestamp('fecha_cancelacion')->nullable();
            $table->timestamps();

            $table->index('boleta_id');
            $table->index('tipo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boleta_movimientos');
    }
};
