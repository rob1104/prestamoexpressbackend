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
        Schema::create('nota_creditos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boleta_id')->constrained('boletas');
            $table->string('tipo_prestamo');
            $table->decimal('cantidad', 18, 2);
            $table->decimal('cantidad_sugerida', 18, 2);
            $table->string('estatus');
            $table->string('motivo_cancelacion')->nullable();
            $table->datetime('fecha_cancelacion')->nullable();
            $table->foreignId('caja_id')->constrained('cajas');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('user_autorizo_id')->nullable()->constrained('users');
            $table->foreignId('user_cancelo_id')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nota_creditos');
    }
};
