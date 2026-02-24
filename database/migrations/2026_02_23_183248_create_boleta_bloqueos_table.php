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
        Schema::create('boleta_bloqueos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boleta_id')->constrained('boletas')->cascadeOnDelete();
            $table->string('motivo', 120);
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();

            // Desbloqueo
            $table->string('motivo_desbloqueo', 120)->nullable();
            $table->foreignId('usuario_desbloqueo_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('desbloqueado_at')->nullable();

            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('boleta_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boleta_bloqueos');
    }
};
