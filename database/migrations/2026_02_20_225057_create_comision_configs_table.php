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
        Schema::create('comision_configs', function (Blueprint $table) {
            $table->id();
            $table->string('categorias'); // Ejemplo: 1/2/3/5/6/7/
            $table->decimal('limite_inferior', 15, 2);
            $table->decimal('limite_superior', 15, 2);
            $table->integer('mes_inferior');
            $table->integer('mes_superior');
            $table->decimal('porcentaje_comision', 5, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comision_configs');
    }
};
