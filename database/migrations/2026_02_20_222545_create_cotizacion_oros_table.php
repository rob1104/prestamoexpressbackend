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
        Schema::create('cotizacion_oros', function (Blueprint $table) {
            $table->id();
            $table->string('categoria'); // GRAMOS, MONEDAS
            $table->string('descripcion'); // 10K, 14K, 50 PESOS, etc.
            $table->decimal('precio_nuevo', 10, 2)->default(0);
            $table->decimal('precio_bueno', 10, 2)->default(0);
            $table->decimal('precio_excelente', 10, 2)->default(0);
            $table->decimal('precio_compra', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cotizacion_oros');
    }
};
