<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. TABLA: DATOS GENERALES DE LA VENTA (ENCABEZADO)
        Schema::create('ventas_electronicos_general', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sucursal_id')->default(1);
            $table->char('tipo_venta', 1)->comment('T=Total, S=Separado, P=Pagos');
            $table->char('modo', 1)->comment('C=Contado, V=Voucher, D=Debito, M=Mixto');
            $table->date('fecha_movimiento');
            $table->string('nota_mostrador')->nullable();

            // Llaves foráneas a catálogos
            $table->unsignedBigInteger('vendedor_id')->nullable();
            $table->unsignedBigInteger('empresa_id')->nullable();
            $table->string('cliente', 150);
            $table->string('no_bolsa', 50)->nullable();

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
        Schema::create('ventas_electronicos_detalle', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('venta_id');
            $table->integer('consecutivo');

            // Detalles del electrónico
            $table->string('codigo', 50)->comment('Código del producto en inventario');
            $table->string('clasificacion', 50)->nullable();
            $table->string('subclasificacion', 50)->nullable();
            $table->string('descripcion', 255);

            $table->integer('cantidad');
            $table->decimal('precio', 12, 2);
            $table->decimal('importe', 12, 2);

            $table->timestamps();

            // Llave foránea
            $table->foreign('venta_id')
                ->references('id')->on('ventas_electronicos_general')
                ->onDelete('cascade');
        });

        // 3. TABLA: HISTORIAL DE PAGOS DE LA VENTA
        Schema::create('ventas_electronicos_pagos', function (Blueprint $table) {
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
                ->references('id')->on('ventas_electronicos_general')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas_electronicos_pagos');
        Schema::dropIfExists('ventas_electronicos_detalle');
        Schema::dropIfExists('ventas_electronicos_general');
    }
};
