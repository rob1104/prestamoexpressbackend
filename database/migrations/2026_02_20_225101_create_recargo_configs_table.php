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
        Schema::create('recargo_configs', function (Blueprint $table) {
            $table->id();
            $table->integer('dia_inicio');
            $table->integer('dia_fin');
            $table->decimal('valor', 10, 2);
            $table->char('tipo', 1); // 'D' para Diario o 'T' para Total
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recargo_configs');
    }
};
