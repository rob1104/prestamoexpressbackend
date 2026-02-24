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
        Schema::create('boleta_partidas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boleta_id')->constrained('boletas')->cascadeOnDelete();

            $table->enum('tipo', ['kilate', 'moneda']);
            // Para kilates: "8K", "10K", "14K", "18K", "21K", "oro_fino", "medalla"
            // Para monedas: "2", "2.5", "5", "10", "20", "50"
            $table->string('subtipo', 20);

            $table->decimal('gramos_cantidad', 10, 3)->default(0); // gramos o piezas
            $table->decimal('costo_unitario', 10, 2)->default(0);  // costo x gr o costo x moneda
            $table->decimal('valor', 12, 2)->default(0);           // gramos * costo_unitario
            $table->string('descripcion', 120)->nullable();        // descripción de la prenda

            $table->timestamps();

            $table->index('boleta_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boleta_partidas');
    }
};
