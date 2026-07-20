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
        Schema::table('cierre_diarios', function (Blueprint $table) {
            $table->decimal('ventas_joyeria', 15, 2)->default(0)->after('salidas_otros');
            $table->decimal('ventas_electronicos', 15, 2)->default(0)->after('ventas_joyeria');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cierre_diarios', function (Blueprint $table) {
            $table->dropColumn('ventas_joyeria');
            $table->dropColumn('ventas_electronicos');
        });
    }
};
