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
        Schema::create('sucursal_configs', function (Blueprint $table) {
            $table->id();
            // Datos Generales
            $table->integer('no_sucursal')->default(1);
            $table->string('nombre_sucursal');
            $table->string('razon_social');
            $table->string('calle_num');
            $table->string('colonia');
            $table->string('municipio');
            $table->string('estado');
            $table->string('codigo_postal', 10);
            $table->string('rfc', 15);
            $table->string('telefono_1');
            $table->string('telefono_2')->nullable();
            $table->decimal('fondo_fijo_mn', 15, 2)->default(0);
            $table->decimal('fondo_fijo_usd', 15, 2)->default(0);
            $table->decimal('comision_tc', 15, 5)->default(0);
            $table->decimal('comision_td', 15, 5)->default(0);
            $table->decimal('p_com_cheques_fed', 15, 5)->default(0);
            $table->decimal('min_cheques_fed', 15, 2)->default(0);
            $table->decimal('m_com_cheques_fed', 15, 2)->default(0);
            $table->string('tomar_festivos', 16)->default("SI");
            $table->string('tamano_ticket', 32);
            $table->string('salida_cartera_de', 32);
            $table->string('salida_cartera_a', 32);
            $table->decimal('min_pago_facil', 15, 2)->default(0);
            $table->decimal('p_com_cheques_fed', 15, 5)->default(0);
            $table->decimal('costo_reporte_extravio', 15, 2)->default(0);

            // Porcentajes e Impuestos
            $table->decimal('p_comision', 5, 2);
            $table->decimal('p_iva', 5, 2);
            $table->decimal('p_avaluo', 5, 2);
            $table->decimal('capital_trabajo', 15, 2)->default(0);

            // Tiempos
            $table->integer('dias_comercializacion')->default(15);
            $table->integer('dias_adjudicar')->default(15);

            // Separación de Comisiones
            $table->decimal('p_almacenaje', 5, 2)->default(0);
            $table->decimal('p_administracion', 5, 2)->default(0);
            $table->decimal('p_custodia', 5, 2)->default(0);
            $table->decimal('p_interes_dividido', 5, 2)->default(0);
            $table->decimal('p_iva_interes', 5, 2)->default(0);

            // Datos de Ticket Electrónicos
            $table->string('linea_01')->nullable();
            $table->string('linea_02')->nullable();
            $table->string('linea_03')->nullable();
            $table->string('linea_04')->nullable();
            $table->string('linea_05')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sucursal_configs');
    }
};
