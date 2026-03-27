<!DOCTYPE html>
<html>
<head> 
    <style>
        @page { margin: 1cm; }
        body { font-family: 'Courier', sans-serif; font-size: 10px; color: #333; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        .border-bottom { border-bottom: 1px solid #000; }
        .bg-grey { background-color: #eee; }
        .main-table { width: 100%; border-collapse: collapse; }
        .column-table { width: 100%; border: none; }
        .column-table td { padding: 3px; border: none; }
        .header { margin-bottom: 20px; }
        .total-box { border: 1px solid #000; padding: 5px; margin-top: 10px; }
    </style>
</head>
<body>
<div class="header text-center">
    <div style="font-size: 14px;" class="fw-bold">{{ $sucursal }}</div>
    <div>{{ $empresa }}</div>
    <div style="font-size: 12px; margin-top: 5px; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 3px;">
        FLUJO DE CAJA GENERAL CORPORATIVO
    </div>
    <div class="fw-bold">{{ $reporte['config']['fecha_rango'] }}</div>
</div>

<table class="main-table">
    <tr>
        <td style="width: 48%; vertical-align: top; border-right: 1px solid #000; padding-right: 10px;">
            <div class="bg-grey fw-bold text-center" style="padding: 5px; border: 1px solid #000;">E N T R A D A S</div>

            <table class="column-table">
                <tr>
                    <td class="fw-bold">SALDO INICIAL</td>
                    <td class="text-right fw-bold">$ {{ number_format($reporte['config']['saldo_inicial'], 2) }}</td>
                </tr>
                <tr><td colspan="2" class="fw-bold border-bottom">PAGOS</td></tr>
                <tr><td>+ Capital Recuperado</td><td class="text-right">$ {{ number_format($reporte['entradas']['pagos_capital'], 2) }}</td></tr>
                <tr><td>+ Intereses e IVA</td><td class="text-right">$ {{ number_format($reporte['entradas']['pagos_interes'], 2) }}</td></tr>
                <tr><td>+ Recargos</td><td class="text-right">$ {{ number_format($reporte['entradas']['pagos_recargos'], 2) }}</td></tr>

                <tr><td colspan="2" class="fw-bold border-bottom" style="padding-top: 10px;">OTROS</td></tr>
                <tr><td>+ Entradas Diversas</td><td class="text-right">$ {{ number_format($reporte['entradas']['otros'], 2) }}</td></tr>
            </table>

            <div class="total-box text-right fw-bold" style="background-color: #f9f9f9;">
                TOTAL ENTRADAS: $ {{ number_format($reporte['config']['saldo_inicial'] + $reporte['entradas']['pagos_capital'] + $reporte['entradas']['pagos_interes'] + $reporte['entradas']['pagos_recargos'] + $reporte['entradas']['otros'], 2) }}
            </div>
        </td>

        <td style="width: 48%; vertical-align: top; padding-left: 10px;">
            <div class="bg-grey fw-bold text-center" style="padding: 5px; border: 1px solid #000;">S A L I D A S</div>

            <table class="column-table">
                <tr><td colspan="2" class="fw-bold border-bottom">PRÉSTAMOS</td></tr>
                <tr><td>- Préstamos Nuevos</td><td class="text-right">$ {{ number_format($reporte['salidas']['prestamos'], 2) }}</td></tr>

                <tr><td colspan="2" class="fw-bold border-bottom" style="padding-top: 10px;">GASTOS</td></tr>
                <tr><td>- Gastos / Salidas Varios</td><td class="text-right">$ {{ number_format($reporte['salidas']['otros'], 2) }}</td></tr>
            </table>

            <div class="total-box text-right fw-bold" style="margin-top: 100px;">
                TOTAL SALIDAS: $ {{ number_format($reporte['salidas']['prestamos'] + $reporte['salidas']['otros'], 2) }}
            </div>
        </td>
    </tr>
</table>

<div style="margin-top: 40px; border: 2px solid #000; padding: 10px; background-color: #eee;">
    <table style="width: 100%;">
        <tr>
            <td style="font-size: 14px;" class="fw-bold">SALDO FINAL EN CAJA:</td>
            <td style="font-size: 16px;" class="text-right fw-bold">
                $ {{ number_format(($reporte['config']['saldo_inicial'] + $reporte['entradas']['pagos_capital'] + $reporte['entradas']['pagos_interes'] + $reporte['entradas']['pagos_recargos'] + $reporte['entradas']['otros']) - ($reporte['salidas']['prestamos'] + $reporte['salidas']['otros']), 2) }}
            </td>
        </tr>
    </table>
</div>
</body>
</html>
