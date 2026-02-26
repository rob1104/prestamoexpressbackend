<?php

use App\Models\User;
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
        Schema::create('boleta_tradicionals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boleta_id')->constrained('boletas');
            $table->integer('refrendo');
            $table->datetime('fecha_vencimiento')->nullable();
            $table->integer('dias_reales');
            $table->decimal('capital', 18, 2);
            $table->decimal('interes', 18, 2);
            $table->decimal('almacenaje', 18, 2);
            $table->decimal('administracion', 18, 2);
            $table->decimal('custodia', 18, 2);
            $table->decimal('interesdividido', 18, 2);
            $table->decimal('iva_interes', 18, 2);
            $table->string('estatus', 2);
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('user_cancel_id')->nullable()->constrained('users');
            $table->dateTime('fecha_cancelacion')->nullable();
            $table->string('motivo_cancelacion', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boleta_tradicionals');
    }
};
