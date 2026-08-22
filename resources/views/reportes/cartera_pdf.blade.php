<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Courier', sans-serif; font-size: 12px; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 5px; }
        .bg-grey { background-color: #f2f2f2; }
        .header-table td { border: none; }
        .page-break { page-break-after: always; }
        .section-title { background: #eee; text-transform: uppercase; font-weight: bold; padding: 8px; }
    </style>
</head>
<body>
<table class="header-table">
    <tr>
        <td style="width: 30%">FECHA: {{ date('d-M-Y') }}</td>
        <td class="text-center fw-bold">
            {{ $config['sucursal'] }}<br>
            {{ $config['empresa'] }}<br>
            CARTERA
        </td>
        <td class="text-right" style="width: 30%">PAG: 1</td>
    </tr>
</table>

<div class="text-center fw-bold" style="margin: 20px 0; border: 2px solid #000; padding: 5px;">CARTERA</div>
<p class="fw-bold">FECHA DE CARTERA: {{ $data['fecha_reporte'] }}</p>

<table>
    <tr class="section-title"><td colspan="4">1. MOVIMIENTOS DEL DÍA (PRÉSTAMOS NUEVOS)</td></tr>
    <tr>
        <td style="width: 50%">Préstamos realizados en la fecha</td>
        <td class="text-center">{{ $data['resumen']['prestamos']['cantidad'] }}</td>
        <td class="text-right">$ {{ number_format($data['resumen']['prestamos']['monto'], 2) }}</td>
        <td class="text-right fw-bold">$ {{ number_format($data['resumen']['prestamos']['monto'], 2) }}</td>
    </tr>
    <tr class="section-title"><td colspan="4">2. RECUPERACIÓN DE CARTERA (COBRANZA)</td></tr>
    <tr>
        <td>Cobranza (Capital + Utilidad)</td>
        <td class="text-center">{{ $data['resumen']['cobranza']['cantidad'] }}</td>
        <td class="text-right">$ {{ number_format($data['resumen']['cobranza']['capital'], 2) }}</td>
        <td class="text-right fw-bold">$ {{ number_format($data['resumen']['cobranza']['total'], 2) }}</td>
    </tr>
    <tr class="section-title"><td colspan="4">3. ESTADO DE CARTERA AL CORTE</td></tr>
    <tr><td>Cartera Vigente</td><td class="text-center">{{ $data['cartera']['vigente_cant'] }}</td><td colspan="2" class="text-right">$ {{ number_format($data['cartera']['vigente_monto'], 2) }}</td></tr>
    <tr><td>Cartera Vencida</td><td class="text-center">{{ $data['cartera']['vencida_cant'] }}</td><td colspan="2" class="text-right">$ {{ number_format($data['cartera']['vencida_monto'], 2) }}</td></tr>
    <tr class="fw-bold"><td>Posible Adjudicación</td><td class="text-center">{{ $data['cartera']['adjudicada_cant'] }}</td><td colspan="2" class="text-right">$ {{ number_format($data['cartera']['adjudicada_monto'], 2) }}</td></tr>
</table>

<div class="page-break"></div>

<div class="text-center fw-bold">CARTERA</div>
<div class="text-center italic">Información Detallada por Producto</div>

<p class="fw-bold">P A G O S</p>
<table>
    <tr class="bg-grey"><th>CONCEPTO</th><th>CAPITAL</th><th>COMISIÓN</th><th>TOTAL</th></tr>
    <tr>
        <td>Préstamos Nuevos</td>
        <td class="text-right">{{ number_format($data['detalle']['pagos']['nuevos']['capital'], 2) }}</td>
        <td class="text-right">{{ number_format($data['detalle']['pagos']['nuevos']['comision'], 2) }}</td>
        <td class="text-right">{{ number_format($data['detalle']['pagos']['nuevos']['capital'] + $data['detalle']['pagos']['nuevos']['comision'], 2) }}</td>
    </tr>
    <tr class="fw-bold">
        <td>Posible Adjudicación ({{ $data['detalle']['pagos']['adjudicado']['cantidad'] }})</td>
        <td class="text-right">{{ number_format($data['detalle']['pagos']['adjudicado']['capital'], 2) }}</td>
        <td class="text-right">{{ number_format($data['detalle']['pagos']['adjudicado']['comision'], 2) }}</td>
        <td class="text-right">{{ number_format($data['detalle']['pagos']['adjudicado']['capital'] + $data['detalle']['pagos']['adjudicado']['comision'], 2) }}</td>
    </tr>
</table>

<p class="fw-bold">T R A D I C I O N A L</p>
<table>
    <tr class="bg-grey"><th>CONCEPTO</th><th>CAPITAL</th><th>COMISIÓN</th><th>TOTAL</th></tr>
    <tr>
        <td>Préstamos Nuevos</td>
        <td class="text-right">{{ number_format($data['detalle']['tradicional']['nuevos']['capital'], 2) }}</td>
        <td class="text-right">{{ number_format($data['detalle']['tradicional']['nuevos']['comision'], 2) }}</td>
        <td class="text-right">{{ number_format($data['detalle']['tradicional']['nuevos']['capital'] + $data['detalle']['tradicional']['nuevos']['comision'], 2) }}</td>
    </tr>
    <tr>
        <td>Por Cobrar Vigente</td>
        <td class="text-right">{{ number_format($data['detalle']['tradicional']['vigente']['capital'], 2) }}</td>
        <td class="text-right">{{ number_format($data['detalle']['tradicional']['vigente']['comision'], 2) }}</td>
        <td class="text-right">{{ number_format($data['detalle']['tradicional']['vigente']['capital'] + $data['detalle']['tradicional']['vigente']['comision'], 2) }}</td>
    </tr>
    <tr>
        <td>Por Cobrar Vencido</td>
        <td class="text-right">{{ number_format($data['detalle']['tradicional']['vencido']['capital'], 2) }}</td>
        <td class="text-right">{{ number_format($data['detalle']['tradicional']['vencido']['comision'], 2) }}</td>
        <td class="text-right">{{ number_format($data['detalle']['tradicional']['vencido']['capital'] + $data['detalle']['tradicional']['vencido']['comision'], 2) }}</td>
    </tr>
    <tr>
        <td>Liquidaciones Normales</td>
        <td class="text-right">{{ number_format($data['detalle']['tradicional']['liq_normales']['capital'], 2) }}</td>
        <td class="text-right">{{ number_format($data['detalle']['tradicional']['liq_normales']['comision'], 2) }}</td>
        <td class="text-right">{{ number_format($data['detalle']['tradicional']['liq_normales']['capital'] + $data['detalle']['tradicional']['liq_normales']['comision'], 2) }}</td>
    </tr>
    <tr>
        <td>Liquidaciones Abonos a Capital</td>
        <td class="text-right">{{ number_format($data['detalle']['tradicional']['liq_abonos']['capital'], 2) }}</td>
        <td class="text-right">{{ number_format($data['detalle']['tradicional']['liq_abonos']['comision'], 2) }}</td>
        <td class="text-right">{{ number_format($data['detalle']['tradicional']['liq_abonos']['capital'] + $data['detalle']['tradicional']['liq_abonos']['comision'], 2) }}</td>
    </tr>
    <tr>
        <td>Liquidaciones Cámbiate a Pagos</td>
        <td class="text-right">{{ number_format($data['detalle']['tradicional']['liq_cambio']['capital'], 2) }}</td>
        <td class="text-right">{{ number_format($data['detalle']['tradicional']['liq_cambio']['comision'], 2) }}</td>
        <td class="text-right">{{ number_format($data['detalle']['tradicional']['liq_cambio']['capital'] + $data['detalle']['tradicional']['liq_cambio']['comision'], 2) }}</td>
    </tr>
    <tr>
        <td>Adjudicaciones</td>
        <td class="text-right">{{ number_format($data['detalle']['tradicional']['adj_normal']['capital'], 2) }}</td>
        <td class="text-right">{{ number_format($data['detalle']['tradicional']['adj_normal']['comision'], 2) }}</td>
        <td class="text-right">{{ number_format($data['detalle']['tradicional']['adj_normal']['capital'] + $data['detalle']['tradicional']['adj_normal']['comision'], 2) }}</td>
    </tr>
    <tr>
        <td>Adjudicaciones de Oro</td>
        <td class="text-right">{{ number_format($data['detalle']['tradicional']['adj_oro']['capital'], 2) }}</td>
        <td class="text-right">{{ number_format($data['detalle']['tradicional']['adj_oro']['comision'], 2) }}</td>
        <td class="text-right">{{ number_format($data['detalle']['tradicional']['adj_oro']['capital'] + $data['detalle']['tradicional']['adj_oro']['comision'], 2) }}</td>
    </tr>
    <tr>
        <td>Refrendos</td>
        <td class="text-right">{{ number_format($data['detalle']['tradicional']['refrendos']['capital'], 2) }}</td>
        <td class="text-right">{{ number_format($data['detalle']['tradicional']['refrendos']['comision'], 2) }}</td>
        <td class="text-right">{{ number_format($data['detalle']['tradicional']['refrendos']['capital'] + $data['detalle']['tradicional']['refrendos']['comision'], 2) }}</td>
    </tr>
    <tr class="fw-bold">
        <td>Posible Adjudicación ({{ $data['detalle']['tradicional']['adjudicado']['cantidad'] }})</td>
        <td class="text-right">{{ number_format($data['detalle']['tradicional']['adjudicado']['capital'], 2) }}</td>
        <td class="text-right">{{ number_format($data['detalle']['tradicional']['adjudicado']['comision'], 2) }}</td>
        <td class="text-right">{{ number_format($data['detalle']['tradicional']['adjudicado']['capital'] + $data['detalle']['tradicional']['adjudicado']['comision'], 2) }}</td>
    </tr>
</table>

<div class="text-right fw-bold" style="font-size: 16px; margin-top: 30px;">
    TOTAL CARTERA: $ {{ number_format($data['totales']['total_general'], 2) }}
</div>

<div class="page-break"></div>

<div class="text-center fw-bold" style="margin: 20px 0; border: 2px solid #000; padding: 5px;">RESUMEN DE CAPITAL</div>
<p class="fw-bold">FECHA DE CARTERA: {{ $data['fecha_reporte'] }}</p>

<table style="width: 70%; margin: 0 auto;">
    <tr>
        <td class="fw-bold" style="font-size: 16px;">CAPITAL :</td>
        <td class="text-right fw-bold" style="font-size: 16px;">$ {{ number_format($data['resumen_capital']['capital'], 2) }}</td>
    </tr>
    <tr><td colspan="2" style="border:none; height: 10px;"></td></tr>
    
    <tr>
        <td>+ GASTOS CARTERA PENDIENTE :</td>
        <td class="text-right">{{ number_format($data['resumen_capital']['gastos_cartera_pendiente'], 2) }}</td>
    </tr>
    <tr>
        <td>+ GASTOS PENDIENTES DE PAGO :</td>
        <td class="text-right">{{ number_format($data['resumen_capital']['gastos_pendientes_pago'], 2) }}</td>
    </tr>
    <tr>
        <td>+ SALDO EN CAJA :</td>
        <td class="text-right">{{ number_format($data['resumen_capital']['saldo_en_caja'], 2) }}</td>
    </tr>
    <tr>
        <td>+ CONTRATOS VIGENTES :</td>
        <td class="text-right">{{ number_format($data['resumen_capital']['contratos_vigentes'], 2) }}</td>
    </tr>
    <tr>
        <td>+ COMPRAS MENORES DE 100 :</td>
        <td class="text-right">{{ number_format($data['resumen_capital']['compras_menores_100'], 2) }}</td>
    </tr>
    <tr>
        <td>- ENTRADAS POR PRÉSTAMO :</td>
        <td class="text-right">{{ number_format($data['resumen_capital']['entradas_prestamo'], 2) }}</td>
    </tr>
    <tr>
        <td>- RECARGOS :</td>
        <td class="text-right">{{ number_format($data['resumen_capital']['recargos'], 2) }}</td>
    </tr>
    <tr>
        <td>- ALMACENAJE :</td>
        <td class="text-right">{{ number_format($data['resumen_capital']['almacenaje'], 2) }}</td>
    </tr>
    <tr>
        <td>- VENTA DE APARATOS :</td>
        <td class="text-right">{{ number_format($data['resumen_capital']['venta_aparatos'], 2) }}</td>
    </tr>
    <tr>
        <td>- VENTA DE ORO :</td>
        <td class="text-right">{{ number_format($data['resumen_capital']['venta_oro'], 2) }}</td>
    </tr>
    <tr>
        <td>- ENTRADAS VARIAS :</td>
        <td class="text-right">{{ number_format($data['resumen_capital']['entradas_varias'], 2) }}</td>
    </tr>
    <tr>
        <td>- ABONITOS :</td>
        <td class="text-right">{{ number_format($data['resumen_capital']['abonitos'], 2) }}</td>
    </tr>
    <tr><td colspan="2" style="border:none; height: 10px;"></td></tr>
    <tr>
        <td class="fw-bold" style="text-align: right; font-size: 14px;">DIFERENCIA :</td>
        <td class="text-right fw-bold" style="font-size: 14px;">$ {{ number_format($data['resumen_capital']['diferencia'], 2) }}</td>
    </tr>
</table>

</body>
</html>
