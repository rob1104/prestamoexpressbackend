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
        Schema::create('boletas', function (Blueprint $table) {
            // Relaciones principales
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('categoria_id')->constrained('categorias')->restrictOnDelete();
            $table->foreignId('promocion_id')->nullable()->constrained('promocions')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();

            // Identificación de la bolsa física
            $table->unsignedInteger('no_bolsa');

            // Tipo y plan de pago
            $table->enum('tipo_prestamo', ['tradicional', 'pagos'])->default('tradicional');
            $table->unsignedTinyInteger('meses')->default(1);    // plazo en meses
            $table->unsignedTinyInteger('periodo')->default(0);  // 0=sin periodo, 7=semanal, 15=quincenal, 30=mensual
            $table->unsignedTinyInteger('pagos')->default(1);    // número de pagos (1 = tradicional)

            // Valores económicos
            $table->decimal('prestamo', 12, 2)->default(0);
            $table->decimal('valor_comercial', 12, 2)->default(0);
            $table->decimal('p_interes', 6, 2)->default(0);      // % de comisión/interés
            $table->decimal('comision', 12, 2)->default(0);       // comisión total
            $table->decimal('iva_comision', 12, 2)->default(0);
            $table->decimal('pago_fijo', 12, 2)->default(0);      // pago parcial (para tipo pagos)
            $table->decimal('ultimo_pago', 12, 2)->default(0);    // último pago (puede diferir)
            $table->decimal('total_pagar', 12, 2)->default(0);    // prestamo + comision + iva

            // Fechas
            $table->date('fecha_boleta');
            $table->date('fecha_vencimiento');

            // Estatus
            $table->enum('estatus', ['RE','EN','CF','CA','AC','LI','PE','CV'])
                ->default('PE');

            // Snapshot del costo x gramo al momento de alta (para auditoría)
            $table->decimal('cotizacion_valor', 10, 2)->default(0);

            $table->timestamps();

            // Índices
            $table->index('cliente_id');
            $table->index('estatus');
            $table->index('fecha_vencimiento');
            $table->index('tipo_prestamo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boletas');
    }
};
