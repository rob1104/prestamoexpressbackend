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
            $table->string('referencia_id')->nullable()->after('user_id');
            $table->text('observaciones')->nullable()->after('denominacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movimientos_cajas', function (Blueprint $table) {
            $table->dropColumn('referencia_id', 'observaciones');
        });
    }
};
