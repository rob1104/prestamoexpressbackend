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
        Schema::create('boleta_reportes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boleta_id')->constrained('boletas')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();

            $table->date('fecha_reporte');
            $table->date('fecha_termino')->nullable();
            $table->string('motivo_termino', 10)->nullable();

            // Quitar reporte
            $table->date('fecha_quito_reporte')->nullable();
            $table->string('motivo_quito_reporte', 120)->nullable();
            $table->foreignId('usuario_quito_reporte_id')->nullable()->constrained('users')->nullOnDelete();

            $table->boolean('activo')->default(true);
            $table->boolean('pagado')->default(false);
            $table->timestamps();

            $table->index('boleta_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boleta_reportes');
    }
};
