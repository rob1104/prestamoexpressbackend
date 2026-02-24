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
        Schema::create('boleta_vencimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boleta_id')->constrained('boletas')->cascadeOnDelete();

            $table->unsignedTinyInteger('no_pago')->default(1);   // 1 para tradicional, 1-N para pagos
            $table->date('fecha_vencimiento');
            $table->unsignedSmallInteger('dias_reales')->default(0);

            $table->decimal('capital', 12, 2)->default(0);
            $table->decimal('comision', 12, 2)->default(0);
            $table->decimal('iva_comision', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);          // capital + comision + iva

            $table->enum('estatus', ['pendiente', 'pagado', 'vencido', 'cancelado'])
                ->default('pendiente');

            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['boleta_id', 'no_pago']);
            $table->index('fecha_vencimiento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boleta_vencimientos');
    }
};
