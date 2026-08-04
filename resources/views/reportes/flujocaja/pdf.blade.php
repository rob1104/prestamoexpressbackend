<!DOCTYPE html>
<html>
<head> 
    <style>
        @page { margin: 0.5cm; }
        body { font-family: 'Courier', monospace; font-size: 9px; color: #000; line-height: 1.1; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .fw-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        
        .header-main { text-align: center; margin-bottom: 5px; }
        .header-title { font-size: 12px; font-weight: bold; }
        .header-subtitle { font-size: 10px; margin-top: 1px; }
        .mt-1 { margin-top: 2px; }
        
        .info-grid { width: 100%; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 2px 0; margin-bottom: 5px; font-size: 9px; }
        .info-grid td { padding: 1px; }
        
        .section-header { font-weight: bold; text-align: center; padding: 2px; border-top: 1px solid #000; border-bottom: 1px solid #000; margin-top: 5px; font-size: 10px; }
        .section-header-col { font-weight: bold; text-align: center; padding: 2px; border-top: 1px solid #000; border-bottom: 1px solid #000; margin-bottom: 5px; font-size: 10px; }
        
        .table-split { width: 100%; border-collapse: collapse; }
        .table-split > tbody > tr > td.column-left { width: 49%; vertical-align: top; border-right: 1px solid #ccc; padding-right: 5px; }
        .table-split > tbody > tr > td.column-right { width: 49%; vertical-align: top; padding-left: 5px; }
        
        .data-table { width: 100%; border-collapse: collapse; font-size: 9px; }
        .data-table td { padding: 1px 0; }
        .data-table .cat-title { font-weight: bold; padding-top: 4px; padding-bottom: 1px; }
        .data-table .item-row td { padding-left: 5px; }
        .data-table .subitem-row td { padding-left: 15px; }
        .data-table .subtotal-row td { padding-top: 2px; border-top: 1px solid #999; font-weight: bold; }
        
        .total-box { 
            width: 100%; 
            border-top: 1px solid #000; 
            border-bottom: 1px solid #000; 
            padding: 2px 0; 
            font-weight: bold; 
            margin-top: 5px;
            font-size: 10px;
        }
        
        .arqueo-box {
            border: 1px solid #000;
            padding: 5px;
            margin-top: 10px;
            background-color: #f9f9f9;
        }
        .arqueo-table { width: 100%; font-size: 10px; margin-top: 5px; }
        .arqueo-table td { padding: 1px; }
        .diferencia-row { font-weight: bold; font-size: 11px; border-top: 1px solid #000; }
        
        /* Helpers */
        .spacer { height: 5px; }
        .line-bottom { border-bottom: 1px solid #000; }
        .line-top { border-top: 1px solid #000; }
    </style>
</head>
<body>

    <!-- HEADER CORPORATIVO -->
    <div class="header-main">
        <div class="header-title uppercase">{{ $empresa }}</div>
        <div class="header-subtitle">CORPORATIVO EXPRESS S.A. DE C.V.</div>
        <div class="header-subtitle">SISTEMA DE CASAS DE EMPEÑO "SICAE"</div>
        <div class="header-subtitle fw-bold mt-1">FLUJO DE CAJA</div>
    </div>

    <!-- METADATOS -->
    <table class="info-grid">
        <tr>
            <td class="fw-bold" style="width: 15%;">FECHA:</td>
            <td style="width: 45%;">{{ $reporte['config']['fecha_impresion'] }}</td>
            <td class="fw-bold" style="width: 15%;">HORA:</td>
            <td style="width: 25%;" class="text-right">{{ $reporte['config']['hora_impresion'] }}</td>
        </tr>
        <tr>
            <td class="fw-bold">PERÍODO:</td>
            <td class="uppercase">{{ $reporte['config']['fecha_rango'] }}</td>
            <td class="fw-bold">PÁGINA:</td>
            <td class="text-right">1</td>
        </tr>
        <tr>
            <td class="fw-bold">CAJA:</td>
            <td class="uppercase">TODAS</td>
            <td></td>
            <td></td>
        </tr>
    </table>

    <!-- SALDOS INICIALES -->
    <div class="section-header">FLUJO DE CAJA TODAS LAS CAJAS</div>
    <table style="width: 100%; margin-top: 2px; margin-bottom: 5px; font-size: 9px; font-weight: bold;">
        <tr>
            <td style="width: 50%;">FONDO FIJO DE CAJA</td>
            <td class="text-right" style="width: 50%;">$ {{ number_format($reporte['config']['fondo_fijo'], 2) }}</td>
        </tr>
        <tr>
            <td>SALDO INICIAL</td>
            <td class="text-right">$ {{ number_format($reporte['config']['saldo_inicial'], 2) }}</td>
        </tr>
    </table>

    <!-- ENTRADAS Y SALIDAS -->
    <table class="table-split">
        <tr>
            <!-- COLUMNA ENTRADAS -->
            <td class="column-left">
                <div class="section-header-col">E N T R A D A S</div>
                <table class="data-table">
                    <tr><td colspan="2" class="cat-title">PAGOS</td></tr>
                    <tr class="item-row"><td>+ PAGOS:</td><td class="text-right">{{ number_format($reporte['entradas']['pagos']['pagos'], 2) }}</td></tr>
                    <tr class="item-row"><td>+ RECARGOS:</td><td class="text-right">{{ number_format($reporte['entradas']['pagos']['recargos'], 2) }}</td></tr>
                    <tr class="item-row"><td>+ COMISION RESTRUCTURA PRESTAMO:</td><td class="text-right">{{ number_format($reporte['entradas']['pagos']['comision_restructura'], 2) }}</td></tr>
                    <tr class="item-row"><td>+ COMISION CAMBIO PLAN PAGO:</td><td class="text-right">{{ number_format($reporte['entradas']['pagos']['comision_cambio_plan'], 2) }}</td></tr>
                    <tr class="item-row"><td>+ COMISION CAMBIO TRADICIONAL.:</td><td class="text-right">{{ number_format($reporte['entradas']['pagos']['comision_cambio_trad'], 2) }}</td></tr>
                    <tr class="item-row"><td>- NOTAS DE CREDITO:</td><td class="text-right">{{ number_format($reporte['entradas']['pagos']['notas_credito'], 2) }}</td></tr>
                    <tr class="subtotal-row"><td class="text-right"></td><td class="text-right fw-bold">{{ number_format($reporte['entradas']['pagos']['total'], 2) }}</td></tr>

                    <tr><td colspan="2" class="cat-title">TRADICIONAL</td></tr>
                    <tr class="item-row"><td colspan="2">+ REFRENDOS</td></tr>
                    <tr class="subitem-row"><td>COMISION:</td><td class="text-right">{{ number_format($reporte['entradas']['tradicional']['refrendos']['comision'], 2) }}</td></tr>
                    <tr class="subitem-row"><td>RECARGOS:</td><td class="text-right">{{ number_format($reporte['entradas']['tradicional']['refrendos']['recargos'], 2) }}</td></tr>
                    <tr class="subtotal-row"><td class="text-right"></td><td class="text-right fw-bold">{{ number_format($reporte['entradas']['tradicional']['refrendos']['total'], 2) }}</td></tr>

                    <tr class="item-row"><td colspan="2">+ LIQUIDACIONES</td></tr>
                    <tr class="subitem-row"><td>CAPITAL:</td><td class="text-right">{{ number_format($reporte['entradas']['tradicional']['liquidaciones']['capital'], 2) }}</td></tr>
                    <tr class="subitem-row"><td>COMISION:</td><td class="text-right">{{ number_format($reporte['entradas']['tradicional']['liquidaciones']['comision'], 2) }}</td></tr>
                    <tr class="subitem-row"><td>RECARGOS:</td><td class="text-right">{{ number_format($reporte['entradas']['tradicional']['liquidaciones']['recargos'], 2) }}</td></tr>
                    <tr class="subtotal-row"><td class="text-right"></td><td class="text-right fw-bold">{{ number_format($reporte['entradas']['tradicional']['liquidaciones']['total'], 2) }}</td></tr>

                    <tr class="item-row"><td>- NOTAS DE CREDITO:</td><td class="text-right">{{ number_format($reporte['entradas']['tradicional']['notas_credito'], 2) }}</td></tr>
                    <tr class="subtotal-row"><td class="text-right"></td><td class="text-right fw-bold">{{ number_format($reporte['entradas']['tradicional']['total'], 2) }}</td></tr>
                    
                    <tr><td colspan="2"><br></td></tr>
                    <tr class="item-row">
                        <td colspan="2">
                            <table style="width: 100%; border: none;">
                                <tr><td class="fw-bold" style="width: 50%;">VENTAS</td><td class="text-center fw-bold" style="width: 25%;">ELECTRONICOS</td><td class="text-right fw-bold" style="width: 25%;">ORO</td></tr>
                                <tr><td colspan="3" class="line-top"></td></tr>
                                <tr><td>VENTAS:</td><td class="text-center">{{ number_format($reporte['entradas']['ventas']['electronicos']['ventas'], 2) }}</td><td class="text-right">{{ number_format($reporte['entradas']['ventas']['oro']['ventas'], 2) }}</td></tr>
                                <tr><td>SEPARADO Y ABONOS:</td><td class="text-center">{{ number_format($reporte['entradas']['ventas']['electronicos']['separado'], 2) }}</td><td class="text-right">{{ number_format($reporte['entradas']['ventas']['oro']['separado'], 2) }}</td></tr>
                                <tr><td>LIQ SEPARADO:</td><td class="text-center">{{ number_format($reporte['entradas']['ventas']['electronicos']['liq_separado'], 2) }}</td><td class="text-right">{{ number_format($reporte['entradas']['ventas']['oro']['liq_separado'], 2) }}</td></tr>
                                <tr class="subtotal-row"><td class="text-right"></td><td class="text-center fw-bold">{{ number_format($reporte['entradas']['ventas']['electronicos']['total'], 2) }}</td><td class="text-right fw-bold">{{ number_format($reporte['entradas']['ventas']['oro']['total'], 2) }}</td></tr>
                            </table>
                        </td>
                    </tr>

                    <tr><td colspan="2" class="cat-title">PAGOS DE SERVICIOS:</td></tr>
                    <tr class="subitem-row"><td>IMPORTE:</td><td class="text-right">{{ number_format($reporte['entradas']['otros']['pagos_servicios']['importe'], 2) }}</td></tr>
                    <tr class="subitem-row"><td>COMISION:</td><td class="text-right">{{ number_format($reporte['entradas']['otros']['pagos_servicios']['comision'], 2) }}</td></tr>
                    <tr class="item-row"><td>TOTAL DE PAGOS DE SERVICIOS:</td><td class="text-right fw-bold" style="border-top: 1px solid #000;">{{ number_format($reporte['entradas']['otros']['pagos_servicios']['total'], 2) }}</td></tr>

                    <tr><td colspan="2"><br></td></tr>
                    <tr class="item-row"><td>VENTA DE DOLARES:</td><td class="text-right">{{ number_format($reporte['entradas']['otros']['venta_dolares'], 2) }}</td></tr>
                    <tr class="item-row"><td>APORTACIONES DE CAJAS:</td><td class="text-right">{{ number_format($reporte['entradas']['otros']['aportaciones_cajas'], 2) }}</td></tr>
                    <tr class="item-row"><td>NOTAS DE EXTRAVIO:</td><td class="text-right">{{ number_format($reporte['entradas']['otros']['notas_extravio'], 2) }}</td></tr>
                    <tr class="item-row"><td>ENTRADAS A CAJA:</td><td class="text-right">{{ number_format($reporte['entradas']['otros']['entradas_caja'], 2) }}</td></tr>
                    <tr class="item-row"><td>ABONITOS:</td><td class="text-right">{{ number_format($reporte['entradas']['otros']['abonitos'], 2) }}</td></tr>
                </table>
                
                <div class="total-box">
                    <table style="width: 100%;">
                        <tr>
                            <td>TOTAL DE ENTRADAS:</td>
                            <td class="text-right">{{ number_format($reporte['entradas']['total_general'], 2) }}</td>
                        </tr>
                    </table>
                </div>
            </td>

            <!-- COLUMNA SALIDAS -->
            <td class="column-right">
                <div class="section-header-col">S A L I D A S</div>
                <table class="data-table">
                    <tr><td colspan="2" class="cat-title">PAGOS</td></tr>
                    <tr class="item-row"><td>+ PRESTAMOS NUEVOS:</td><td class="text-right">{{ number_format($reporte['salidas']['pagos']['prestamos_nuevos'], 2) }}</td></tr>
                    <tr class="item-row"><td>- CANC. CAPITAL CAMBIO PLAN PAGO:</td><td class="text-right">{{ number_format($reporte['salidas']['pagos']['canc_capital_cambio_plan'], 2) }}</td></tr>
                    <tr class="item-row"><td>- CANC. CAPITAL CAMBIATE TRAD.:</td><td class="text-right">{{ number_format($reporte['salidas']['pagos']['canc_capital_cambio_trad'], 2) }}</td></tr>
                    <tr class="subtotal-row">
                        <td>= TOTAL DE PRESTAMOS NUEVOS:</td>
                        <td class="text-right fw-bold">{{ number_format($reporte['salidas']['pagos']['total'], 2) }}</td>
                    </tr>

                    <tr><td colspan="2" class="cat-title">TRADICIONAL</td></tr>
                    <tr class="item-row"><td>PRESTAMOS NUEVOS:</td><td class="text-right">{{ number_format($reporte['salidas']['tradicional']['prestamos_nuevos'], 2) }}</td></tr>

                    <tr><td colspan="2"><br></td></tr>
                    <tr class="item-row"><td>COMPRA DE DOLARES:</td><td class="text-right">{{ number_format($reporte['salidas']['otros']['compra_dolares'], 2) }}</td></tr>
                    <tr class="item-row"><td>RETIROS DE CAJAS:</td><td class="text-right">{{ number_format($reporte['salidas']['otros']['retiros_cajas'], 2) }}</td></tr>
                    <tr class="item-row"><td>GASTOS GENERALES:</td><td class="text-right">{{ number_format($reporte['salidas']['otros']['gastos_generales'], 2) }}</td></tr>
                    <tr class="item-row"><td>SALIDAS DE CAJA:</td><td class="text-right">{{ number_format($reporte['salidas']['otros']['salidas_caja'], 2) }}</td></tr>
                    <tr class="item-row"><td>COMPRA DE ORO Y PLATA:</td><td class="text-right">{{ number_format($reporte['salidas']['otros']['compra_oro_plata'], 2) }}</td></tr>
                    <tr class="item-row"><td>DEPOSITOS DE VOUCHERS ORO Y ELECT.:</td><td class="text-right">{{ number_format($reporte['salidas']['otros']['depositos_vouchers'], 2) }}</td></tr>
                </table>
                
                <table style="width: 100%; margin-top: 15px;">
                    <tr class="subtotal-row fw-bold">
                        <td>TOTAL DE SALIDAS:</td>
                        <td class="text-right">{{ number_format($reporte['salidas']['total_general'], 2) }}</td>
                    </tr>
                </table>
                
        <div class="section-header-col" style="margin-top: 10px;">CORTE DE DOLARES</div>
        <table class="data-table">
                    <tr class="fw-bold"><td>FONDO FIJO DE CAJA</td><td class="text-right">{{ number_format($reporte['dolares']['fondo_fijo'], 2) }}</td></tr>
                    <tr class="fw-bold"><td>SALDO INICIAL</td><td class="text-right">{{ number_format($reporte['dolares']['saldo_inicial'], 2) }}</td></tr>
                    <tr class="item-row"><td>+ APORTACIONES DE CAJAS:</td><td class="text-right">{{ number_format($reporte['dolares']['aportaciones'], 2) }}</td></tr>
                    <tr class="item-row"><td>- RETIROS DE CAJAS:</td><td class="text-right">{{ number_format($reporte['dolares']['retiros'], 2) }}</td></tr>
                    <tr class="item-row">
                        <td colspan="2">
                            <table style="width: 100%;">
                                <tr>
                                    <td style="width: 40%;">+ COMPRAS:</td>
                                    <td style="width: 30%;">Movimientos: 0</td>
                                    <td class="text-right" style="width: 30%;">{{ number_format($reporte['dolares']['compras'], 2) }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr class="item-row">
                        <td colspan="2">
                            <table style="width: 100%;">
                                <tr>
                                    <td style="width: 40%;">- VENTAS:</td>
                                    <td style="width: 30%;">Movimientos: 0</td>
                                    <td class="text-right" style="width: 30%;">{{ number_format($reporte['dolares']['ventas'], 2) }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr class="subtotal-row fw-bold"><td>TOTAL DE DOLARES:</td><td class="text-right">{{ number_format($reporte['dolares']['total'], 2) }}</td></tr>
                    
                    <tr><td colspan="2"><br></td></tr>
                    <tr class="fw-bold"><td>SALDO FINAL</td><td class="text-right">{{ number_format($reporte['dolares']['saldo_final'], 2) }}</td></tr>
                    <tr class="fw-bold"><td>MENOS FONDO FIJO DE CAJA</td><td class="text-right">{{ number_format($reporte['dolares']['menos_fondo'], 2) }}</td></tr>
                    <tr class="subtotal-row fw-bold"><td></td><td class="text-right">{{ number_format($reporte['dolares']['saldo_final'] - $reporte['dolares']['menos_fondo'], 2) }}</td></tr>
                    
                    <tr><td colspan="2"><br></td></tr>
                    <tr class="fw-bold"><td>TIPO CAMBIO PROMEDIO COMPRA:</td><td class="text-right">{{ number_format($reporte['dolares']['tipo_cambio_compra'], 2) }}</td></tr>
                    <tr class="fw-bold"><td>TIPO CAMBIO PROMEDIO VENTA:</td><td class="text-right">{{ number_format($reporte['dolares']['tipo_cambio_venta'], 2) }}</td></tr>
                    <tr><td colspan="2"><br></td></tr>
                    <tr class="fw-bold"><td>UTILIDAD CAMBIARIA EN VENTA:</td><td class="text-right">{{ number_format($reporte['dolares']['utilidad'], 2) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- ARQUEO FINAL -->
    <div style="width: 80%; margin: 0 auto; margin-top: 10px;">
        <table class="arqueo-table" style="font-size: 10px;">
            <tr>
                <td style="width: 50%;">SALDO FINAL EN CAJA</td>
                <td class="text-right fw-bold" style="width: 50%;">{{ number_format($reporte['config']['saldo_final'], 2) }}</td>
            </tr>
            <tr>
                <td class="text-right">MENOS FONDO DE CAJA</td>
                <td class="text-right">{{ number_format($reporte['config']['fondo_fijo'], 2) }}</td>
            </tr>
            <tr>
                <td class="text-right">MENOS GASTOS POR COMPROBAR</td>
                <td class="text-right">0.00</td>
            </tr>
            <tr class="subtotal-row">
                <td class="text-right fw-bold" style="font-size: 11px;">DIFERENCIA</td>
                <td class="text-right fw-bold" style="font-size: 11px;">{{ number_format($reporte['config']['saldo_final'] - $reporte['config']['fondo_fijo'], 2) }}</td>
            </tr>
        </table>
    </div>

</body>
</html>
