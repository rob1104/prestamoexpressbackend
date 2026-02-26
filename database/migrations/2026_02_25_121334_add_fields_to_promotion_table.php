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
        Schema::table('promocions', function (Blueprint $table) {
            $table->integer('dias_regalo')->default(0); // Para extender la fecha de vencimiento
            $table->decimal('descuento_interes', 5, 2)->default(0);
            $table->decimal('cambio_comision', 5, 2)->default(0); // El nuevo % que se aplicará
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promocions', function (Blueprint $table) {
            $table->dropColumn('dias_regalo');
            $table->dropColumn('descuento_interes');
            $table->dropColumn('cambio_comision');
        });
    }
};
