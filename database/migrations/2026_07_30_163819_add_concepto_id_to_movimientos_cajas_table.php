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
        Schema::table('movimientos_cajas', function (Blueprint $table) {
            $table->foreignId('flujo_concepto_id')->nullable()->constrained('flujo_conceptos')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movimientos_cajas', function (Blueprint $table) {
            $table->dropForeign(['flujo_concepto_id']);
            $table->dropColumn('flujo_concepto_id');
        });
    }
};
