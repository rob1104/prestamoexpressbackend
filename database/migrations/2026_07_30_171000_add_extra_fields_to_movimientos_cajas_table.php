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
        Schema::table('movimientos_cajas', function (Blueprint $table) {
            $table->string('recibido_por')->nullable();
            $table->string('entregado_por')->nullable();
            $table->string('autorizado_por')->nullable();
            $table->string('adicional_1')->nullable();
            $table->string('adicional_2')->nullable();
            $table->boolean('es_pago_relacionado')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movimientos_cajas', function (Blueprint $table) {
            $table->dropColumn([
                'recibido_por',
                'entregado_por',
                'autorizado_por',
                'adicional_1',
                'adicional_2',
                'es_pago_relacionado'
            ]);
        });
    }
};
