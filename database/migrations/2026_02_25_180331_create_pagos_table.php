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
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boleta_id')->constrained('boletas');
            $table->integer('no_pago'); // Corresponde al número de refrendo [cite: 269]
            $table->date('fecha'); // Fecha del movimiento [cite: 271]
            $table->integer('tipo_movimiento'); // 3=Refrendo, 4=Liquidación, 5=Abono [cite: 261, 262, 263]
            $table->decimal('prestamo', 12, 2);
            $table->decimal('interestotal', 12, 2);
            $table->decimal('capital', 12, 2)->default(0);
            $table->decimal('interescapitalizado', 12, 2)->default(0);
            $table->decimal('ivaIC', 12, 2)->default(0);
            $table->decimal('recargosNormal', 12, 2)->default(0);
            $table->decimal('recargoscorridos', 12, 2)->default(0);
            $table->integer('dias_vencidos')->default(0);
            $table->decimal('importe', 12, 2);
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('user_cancelacion_id')->nullable()->constrained('users');
            $table->date('fecha_cancelacion')->nullable();
            $table->string('motivo_cancelacion')->default('*');
            $table->string('estatus', 2)->default('A'); // A=Activo, C=Cancelado [cite: 270, 280]
            $table->string('tipoPrestamo', 2)->default('TR');
            $table->decimal('totalPagado', 12, 2);
            $table->decimal('totalRecibido', 12, 2);
            $table->integer('caja_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
