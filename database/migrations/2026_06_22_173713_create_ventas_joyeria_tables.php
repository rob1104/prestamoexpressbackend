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
        // 1. TABLA: DATOS GENERALES DE LA VENTA (ENCABEZADO)
        Schema::create('ventas_joyeria_general', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sucursal_id')->default(1);
            $table->char('tipo_venta', 1)->comment('T=Total, S=Separado, P=Pagos');
            $table->char('modo', 1)->comment('C=Contado, V=Voucher, D=Debito, M=Mixto');
            $table->date('fecha_movimiento');
            $table->string('nota_mostrador')->nullable();
            $table->unsignedBigInteger('vendedor_id')->nullable();
            $table->string('cliente', 150);

            // Totales Financieros
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('total_pagar', 12, 2)->default(0);
            $table->decimal('pago_recibido', 12, 2)->default(0);

            // Control de Estatus
            $table->char('estatus', 1)->default('A')->comment('A=Activa, C=Cancelada');
            $table->char('estatus_pago', 1)->default('S')->comment('S=Pagada 100%, N=Con Saldo Pendiente');
            $table->date('fecha_limite')->nullable()->comment('Para ventas en Separado');

            // Relaciones de auditoría
            $table->unsignedBigInteger('usuario_id')->comment('Cajero que realizó la venta');
            $table->unsignedBigInteger('caja_id')->default(1);

            $table->timestamps();
        });

        // 2. TABLA: DETALLE DE LOS CONCEPTOS
        Schema::create('ventas_joyeria_detalle', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('venta_id');
            $table->integer('consecutivo');
            $table->integer('cantidad');
            $table->string('categoria', 50)->comment('Ej. ORO, PLATA');
            $table->string('clasificacion', 50)->comment('Ej. ANILLO, CADENA');
            $table->string('concepto', 255)->comment('Descripción tecleada');
            $table->decimal('importe', 12, 2);

            $table->timestamps();

            // Llave foránea (Si se borra la venta, se borra el detalle)
            $table->foreign('venta_id')
                ->references('id')->on('ventas_joyeria_general')
                ->onDelete('cascade');
        });

        // 3. TABLA: HISTORIAL DE PAGOS DE LA VENTA
        Schema::create('ventas_joyeria_pagos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('venta_id');
            $table->integer('no_pago');
            $table->date('fecha_pago');

            $table->decimal('importe', 12, 2)->comment('Lo que se abonó a la deuda');
            $table->decimal('saldo_pagar', 12, 2)->comment('Deuda antes de este pago');
            $table->decimal('resto_pagar', 12, 2)->comment('Deuda después de este pago');

            $table->char('estatus', 1)->default('A');
            $table->char('tipo_venta', 1)->comment('S=Separado, T=Total');
            $table->char('modo', 1)->comment('C=Contado, V=Voucher, D=Debito, M=Mixto');

            // Desglose de cómo pagaron
            $table->decimal('importe_recibido', 12, 2)->default(0);
            $table->decimal('importe_efectivo', 12, 2)->default(0);
            $table->decimal('importe_credito', 12, 2)->default(0);
            $table->decimal('importe_debito', 12, 2)->default(0);

            // Relaciones de auditoría
            $table->unsignedBigInteger('usuario_id');
            $table->unsignedBigInteger('caja_id')->default(1);

            $table->timestamps();

            // Llave foránea
            $table->foreign('venta_id')
                ->references('id')->on('ventas_joyeria_general')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas_joyeria_pagos');
        Schema::dropIfExists('ventas_joyeria_detalle');
        Schema::dropIfExists('ventas_joyeria_general');
    }
};
