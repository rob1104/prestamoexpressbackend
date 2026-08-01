<!DOCTYPE html>
<html>
<head> 
    <style>
        @page { margin: 1cm; }
        body { font-family: 'Courier', monospace; font-size: 10px; color: #000; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .fw-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        
        .header-main { text-align: center; margin-bottom: 10px; }
        .header-title { font-size: 14px; font-weight: bold; }
        .header-subtitle { font-size: 11px; }
        
        .info-grid { width: 100%; border-top: 2px solid #000; border-bottom: 2px solid #000; padding: 5px 0; margin-bottom: 10px; font-size: 10px; }
        .info-grid td { padding: 2px; }
        
        .section-header { font-weight: bold; text-align: center; padding: 4px; border-top: 1px solid #000; border-bottom: 1px solid #000; background-color: #f4f4f4; margin-top: 10px; font-size: 11px; }
        
        .table-split { width: 100%; border-collapse: collapse; }
        .table-split td.column-left { width: 49%; vertical-align: top; border-right: 1px solid #ccc; padding-right: 15px; }
        .table-split td.column-right { width: 49%; vertical-align: top; padding-left: 15px; }
        
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table td { padding: 2px 0; }
        .data-table .cat-title { font-weight: bold; padding-top: 8px; padding-bottom: 2px; }
        .data-table .item-row td { padding-left: 10px; }
        .data-table .subitem-row td { padding-left: 25px; }
        .data-table .subtotal-row td { padding-top: 4px; border-top: 1px solid #999; font-style: italic; }
        
        .total-box { 
            width: 100%; 
            border-top: 2px solid #000; 
            border-bottom: 2px solid #000; 
            padding: 5px 0; 
            font-weight: bold; 
            margin-top: 10px;
            font-size: 11px;
        }
        
        .arqueo-box {
            border: 2px solid #000;
            padding: 10px;
            margin-top: 20px;
            background-color: #f9f9f9;
        }
        .arqueo-table { width: 100%; font-size: 11px; }
        .arqueo-table td { padding: 3px; }
        .diferencia-row { font-weight: bold; font-size: 13px; border-top: 1px solid #000; }
        
        /* Helpers */
        .spacer { height: 10px; }
        .line-bottom { border-bottom: 1px solid #000; }
    </style>
</head>
<body>

    @php
        // Variables auxiliares para totales
        $totalEntradas = $reporte['entradas']['pagos_capital'] + $reporte['entradas']['pagos_interes'] + $reporte['entradas']['pagos_recargos'] + $reporte['entradas']['otros'];
        $totalSalidas = $reporte['salidas']['prestamos'] + $reporte['salidas']['compras_oro'] + $reporte['salidas']['otros'];
        
        // Simulación de Fondo Fijo para que cuadre con el diseño
        $fondoFijo = $reporte['config']['fondo_fijo'] ?? 0.00;
        $saldoInicialHistorico = $reporte['config']['saldo_inicial'] ?? 0;
        
        $saldoFinalCalculado = $fondoFijo + $saldoInicialHistorico + $totalEntradas - $totalSalidas;
        $diferencia = $saldoFinalCalculado;
    @endphp

    <!-- HEADER CORPORATIVO -->
    <div class="header-main">
        <div class="header-title uppercase">{{ $empresa }}</div>
        <div class="header-subtitle">CORPORATIVO EXPRESS S.A. DE C.V.</div>
        <div class="header-subtitle">SISTEMA DE CASAS DE EMPEÑO "SICAE"</div>
    </div>

    <!-- METADATOS -->
    <table class="info-grid">
        <tr>
            <td class="fw-bold" style="width: 15%;">REPORTE:</td>
            <td style="width: 45%;">FLUJO DE CAJA GENERAL</td>
            <td class="fw-bold" style="width: 15%;">FECHA IMP.:</td>
            <td style="width: 25%;" class="text-right">{{ date('d-M-Y') }}</td>
        </tr>
        <tr>
            <td class="fw-bold">CAJA:</td>
            <td class="uppercase">{{ $reporte['config']['caja'] }}</td>
            <td class="fw-bold">HORA IMP.:</td>
            <td class="text-right">{{ date('h:i a') }}</td>
        </tr>
        <tr>
            <td class="fw-bold">PERÍODO:</td>
            <td class="uppercase">{{ $reporte['config']['fecha_rango'] }}</td>
            <td class="fw-bold">PÁGINA:</td>
            <td class="text-right">1</td>
        </tr>
    </table>

    <!-- SALDOS INICIALES -->
    <div class="section-header">SALDOS INICIALES</div>
    <table style="width: 100%; margin-top: 5px; margin-bottom: 15px; font-size: 10px;">
        <tr>
            <td style="width: 50%;">Fondo Fijo de Caja:</td>
            <td class="text-right fw-bold" style="width: 50%;">$ {{ number_format($fondoFijo, 2) }}</td>
        </tr>
        <tr>
            <td>Saldo Inicial Histórico:</td>
            <td class="text-right fw-bold">$ {{ number_format($saldoInicialHistorico, 2) }}</td>
        </tr>
    </table>

    <!-- ENTRADAS Y SALIDAS -->
    <div class="section-header" style="margin-bottom: 10px;">
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%; text-align: center;">E N T R A D A S</td>
                <td style="width: 50%; text-align: center;">S A L I D A S</td>
            </tr>
        </table>
    </div>

    <table class="table-split">
        <tr>
            <!-- COLUMNA ENTRADAS -->
            <td class="column-left">
                <table class="data-table">
                    <tr><td colspan="2" class="cat-title">PAGOS</td></tr>
                    <tr class="item-row"><td>+ Capital Recuperado</td><td class="text-right">$ {{ number_format($reporte['entradas']['pagos_capital'], 2) }}</td></tr>
                    <tr class="item-row"><td>+ Intereses e IVA</td><td class="text-right">$ {{ number_format($reporte['entradas']['pagos_interes'], 2) }}</td></tr>
                    <tr class="item-row"><td>+ Recargos</td><td class="text-right">$ {{ number_format($reporte['entradas']['pagos_recargos'], 2) }}</td></tr>
                    <tr class="item-row"><td>+ Com. Restructura</td><td class="text-right">$ 0.00</td></tr>
                    <tr class="item-row"><td>+ Com. Cambio Plan</td><td class="text-right">$ 0.00</td></tr>
                    <tr class="item-row"><td>+ Com. Cambio Trad.</td><td class="text-right">$ 0.00</td></tr>
                    <tr class="item-row"><td>- Notas de Crédito</td><td class="text-right">$ 0.00</td></tr>
                    <tr class="subtotal-row">
                        <td class="text-right">Subtotal Pagos</td>
                        <td class="text-right fw-bold">$ {{ number_format($reporte['entradas']['pagos_capital'] + $reporte['entradas']['pagos_interes'] + $reporte['entradas']['pagos_recargos'], 2) }}</td>
                    </tr>

                    <tr><td colspan="2" class="cat-title">TRADICIONAL</td></tr>
                    <tr class="item-row"><td colspan="2">+ Refrendos</td></tr>
                    <tr class="subitem-row"><td>Comisión</td><td class="text-right">$ 0.00</td></tr>
                    <tr class="subitem-row"><td>Recargos</td><td class="text-right">$ 0.00</td></tr>
                    <tr class="item-row"><td colspan="2">+ Liquidaciones</td></tr>
                    <tr class="subitem-row"><td>Capital</td><td class="text-right">$ 0.00</td></tr>
                    <tr class="subitem-row"><td>Comisión</td><td class="text-right">$ 0.00</td></tr>
                    <tr class="subitem-row"><td>Recargos</td><td class="text-right">$ 0.00</td></tr>
                    <tr class="subtotal-row">
                        <td class="text-right">Subtotal Tradicional</td>
                        <td class="text-right fw-bold">$ 0.00</td>
                    </tr>
                    
                    <tr><td colspan="2" class="cat-title">VENTAS</td></tr>
                    <tr class="item-row">
                        <td colspan="2">
                            <table style="width: 100%; border: none;">
                                <tr><td></td><td class="text-center fw-bold">ELEC.</td><td class="text-center fw-bold">ORO</td></tr>
                                <tr><td>Ventas</td><td class="text-right">$ 0.00</td><td class="text-right">$ 0.00</td></tr>
                                <tr><td>Abonos</td><td class="text-right">$ 0.00</td><td class="text-right">$ 0.00</td></tr>
                                <tr><td>Liq. Sep</td><td class="text-right">$ 0.00</td><td class="text-right">$ 0.00</td></tr>
                            </table>
                        </td>
                    </tr>
                    <tr class="subtotal-row">
                        <td class="text-right">Subtotal Ventas</td>
                        <td class="text-right fw-bold">$ 0.00</td>
                    </tr>

                    <tr><td colspan="2" class="cat-title">OTROS</td></tr>
                    <tr class="item-row"><td>Pagos de Servicios</td><td class="text-right">$ 0.00</td></tr>
                    <tr class="item-row"><td>Aportaciones de Cajas</td><td class="text-right">$ 0.00</td></tr>
                    <tr class="item-row"><td>Venta de Dólares</td><td class="text-right">$ 0.00</td></tr>
                    <tr class="item-row"><td>Entradas Diversas</td><td class="text-right">$ {{ number_format($reporte['entradas']['otros'], 2) }}</td></tr>
                </table>
                
                <div class="spacer"></div>
                <div class="total-box">
                    <table style="width: 100%;">
                        <tr>
                            <td>TOTAL DE ENTRADAS</td>
                            <td class="text-right">$ {{ number_format($totalEntradas, 2) }}</td>
                        </tr>
                    </table>
                </div>
            </td>

            <!-- COLUMNA SALIDAS -->
            <td class="column-right">
                <table class="data-table">
                    <tr><td colspan="2" class="cat-title">PAGOS</td></tr>
                    <tr class="item-row"><td>+ Préstamos Nuevos</td><td class="text-right">$ 0.00</td></tr>
                    <tr class="item-row"><td>- Canc. Cap. C. Plan Pago</td><td class="text-right">$ 0.00</td></tr>
                    <tr class="item-row"><td>- Canc. Cap. Cambiate Trad.</td><td class="text-right">$ 0.00</td></tr>
                    <tr class="subtotal-row">
                        <td class="text-right">Total Préstamos Nuevos</td>
                        <td class="text-right fw-bold">$ 0.00</td>
                    </tr>

                    <tr><td colspan="2" class="cat-title">TRADICIONAL</td></tr>
                    <tr class="item-row"><td>Préstamos Nuevos</td><td class="text-right">$ {{ number_format($reporte['salidas']['prestamos'], 2) }}</td></tr>

                    <tr><td colspan="2" class="cat-title">GASTOS Y RETIROS</td></tr>
                    <tr class="item-row"><td>Compra de Dólares</td><td class="text-right">$ 0.00</td></tr>
                    <tr class="item-row"><td>Retiros de Cajas</td><td class="text-right">$ 0.00</td></tr>
                    <tr class="item-row"><td>Gastos Generales / Varios</td><td class="text-right">$ {{ number_format($reporte['salidas']['otros'], 2) }}</td></tr>
                    <tr class="item-row"><td>Salidas de Caja</td><td class="text-right">$ 0.00</td></tr>
                    <tr class="item-row"><td>Compra de Oro y Plata</td><td class="text-right">$ {{ number_format($reporte['salidas']['compras_oro'], 2) }}</td></tr>
                    <tr class="item-row"><td>Dep. Vouchers Oro/Elec.</td><td class="text-right">$ 0.00</td></tr>
                </table>
                
                <div style="height: 195px;"></div> <!-- Spacer para emparejar con entradas -->
                
                <div class="total-box">
                    <table style="width: 100%;">
                        <tr>
                            <td>TOTAL DE SALIDAS</td>
                            <td class="text-right">$ {{ number_format($totalSalidas, 2) }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <!-- DOLARES Y DIVISAS -->
    <div class="section-header">CORTE DE DÓLARES Y DIVISAS</div>
    <table style="width: 100%; margin-top: 10px; font-size: 10px;">
        <tr>
            <td style="width: 50%; vertical-align: top; border-right: 1px solid #ccc; padding-right: 15px;">
                <table style="width: 100%;">
                    <tr><td>Fondo Fijo de Caja</td><td class="text-right">$ 0.00</td></tr>
                    <tr><td>Saldo Inicial</td><td class="text-right">$ 0.00</td></tr>
                    <tr><td>+ Aportaciones Cajas</td><td class="text-right">$ 0.00</td></tr>
                    <tr><td>- Retiros Cajas</td><td class="text-right">$ 0.00</td></tr>
                    <tr><td>+ Compras (0 movs)</td><td class="text-right">$ 0.00</td></tr>
                    <tr><td>- Ventas (0 movs)</td><td class="text-right">$ 0.00</td></tr>
                    <tr><td class="line-bottom" colspan="2"></td></tr>
                    <tr><td class="fw-bold">TOTAL DE DOLARES</td><td class="text-right fw-bold">$ 0.00</td></tr>
                </table>
            </td>
            <td style="width: 50%; vertical-align: top; padding-left: 15px;">
                <table style="width: 100%;">
                    <tr><td>Tipo Cambio Promedio Compra:</td><td class="text-right">$ 0.000000</td></tr>
                    <tr><td>Tipo Cambio Promedio Venta:</td><td class="text-right">$ 0.000000</td></tr>
                    <tr><td colspan="2"><br></td></tr>
                    <tr><td class="fw-bold">Utilidad Cambiaria en Venta:</td><td class="text-right fw-bold">$ 0.00</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- ARQUEO FINAL -->
    <div class="arqueo-box">
        <table class="arqueo-table">
            <tr>
                <td style="font-size: 13px;">SALDO FINAL CALCULADO EN CAJA</td>
                <td class="text-right" style="font-size: 14px; font-weight: bold;">$ {{ number_format($saldoFinalCalculado, 2) }}</td>
            </tr>
            <tr class="diferencia-row">
                <td style="padding-top: 5px;">DIFERENCIA (Faltante / Sobrante)</td>
                <td class="text-right" style="padding-top: 5px; font-size: 14px; font-weight: bold;">$ {{ number_format($diferencia, 2) }}</td>
            </tr>
        </table>
    </div>

</body>
</html>
