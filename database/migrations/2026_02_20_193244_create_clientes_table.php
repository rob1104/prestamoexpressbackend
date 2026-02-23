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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 191);
            $table->string('identificacion', 191)->unique();
            $table->enum('clasificacion', ['NUEVO', 'EXCELENTE', 'BUENO', 'REGULAR', 'MALO'])->default('NUEVO');
            $table->string('telefono1', 10);
            $table->string('telefono2', 10)->nullable();
            $table->string('ineFrente', 191)->nullable();
            $table->string('ineReverso', 191)->nullable();
            $table->string('callenum', 191);
            $table->string('colonia', 191);
            $table->string('municipio', 191);
            $table->string('estado', 191);
            $table->string('codPostal', 5);
            $table->string('ocupacion', 191)->nullable();
            $table->longText('observacion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
