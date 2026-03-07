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
        Schema::create('cierre_diarios', function (Blueprint $table) {
            $table->id();
            $table->date('fecha_cierre')->unique(); // La fecha que se está cerrando
            $table->decimal('prestamos_nuevos', 15, 2)->default(0); // Valor01 aprox.
            $table->decimal('capital_recuperado', 15, 2)->default(0); // Valor05 aprox.
            $table->decimal('interes_cobrado', 15, 2)->default(0); // Valor04 aprox.
            $table->decimal('interes_recuperado', 15, 2)->default(0);
            $table->decimal('recargos_cobrados', 15, 2)->default(0);
            $table->decimal('entradas_otros', 15, 2)->default(0);
            $table->decimal('salidas_otros', 15, 2)->default(0);
            $table->integer('boletas_nuevas')->default(0);
            $table->integer('boletas_liquidadas')->default(0);
            $table->foreignId('user_id')->constrained('users'); // Quién ejecutó el cierre
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cierre_diarios');
    }
};
