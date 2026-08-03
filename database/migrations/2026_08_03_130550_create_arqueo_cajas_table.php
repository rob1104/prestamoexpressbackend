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
        Schema::create('arqueo_cajas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caja_id')->constrained('cajas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            
            $table->decimal('importe_sistema', 12, 2)->default(0);
            $table->decimal('importe_arqueo', 12, 2)->default(0);
            $table->decimal('diferencia', 12, 2)->default(0);
            
            $table->json('desglose')->nullable(); // Guardará cuántos billetes de 1000, 500, monedas de 10, etc.
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arqueo_cajas');
    }
};
