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
        Schema::table('boletas', function (Blueprint $table) {
            $table->text('motivo_cancelacion')->nullable()->after('estatus');
            $table->timestamp('cancelada_at')->nullable()->after('motivo_cancelacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('boletas', function (Blueprint $table) {
            $table->dropColumn(['motivo_cancelacion', 'cancelada_at']);
        });
    }
};
