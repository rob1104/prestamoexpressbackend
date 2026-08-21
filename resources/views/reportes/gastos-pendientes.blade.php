<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gastos Pendientes de Pago</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10px; }
        .header { text-align: center; margin-bottom: 20px; font-weight: bold; }
        .title { font-size: 16px; margin-bottom: 5px; }
        .subtitle { font-size: 12px; margin-bottom: 5px; }
        .table-container { width: 100%; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th { border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 5px; text-align: center; font-size: 10px; font-weight: normal; }
        td { padding: 3px 5px; font-size: 10px; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        .classification-title { font-size: 12px; font-weight: bold; padding-top: 15px; padding-bottom: 5px; text-transform: uppercase; }
        .total-row td { border-top: 1px dashed #000; font-weight: bold; padding-top: 5px; }
        .grand-total { font-weight: bold; font-size: 11px; margin-top: 20px; border-top: 2px solid #000; padding-top: 10px; }
        .page-break { page-break-after: always; }
        .header-top { width: 100%; display: table; margin-bottom: 10px; }
        .header-left { display: table-cell; text-align: left; width: 33%; }
        .header-center { display: table-cell; text-align: center; width: 34%; }
        .header-right { display: table-cell; text-align: right; width: 33%; }
    </style>
</head>
<body>

<div class="header">
    <div class="title">PRESTAMO EXPRESS MATRIZ</div>
    <div class="subtitle">CORPORATIVO EXPRESS S.A. DE C.V.</div>
    <div class="subtitle">SISTEMA DE CASAS DE EMPEÑO "SICAE"</div>
    <div class="subtitle">GASTOS PENDIENTES DE PAGO</div>
</div>

<div class="header-top">
    <div class="header-left">
        FECHA: {{ strtoupper($fechaImpresion) }}<br><br>
        GASTOS A LA FECHA DE: &nbsp;&nbsp;&nbsp; <b>{{ strtoupper($fecha_corte) }}</b>
    </div>
    <div class="header-center"></div>
    <div class="header-right">
        HORA: {{ strtoupper($horaImpresion) }}
    </div>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th style="width: 10%;">FOLIO</th>
                <th style="width: 15%;">FECHA</th>
                <th style="width: 60%; text-align: left;">OBSERVACION</th>
                <th style="width: 15%; text-align: right;">IMPORTE</th>
            </tr>
        </thead>
        <tbody>
            @foreach($agrupaciones as $grupo)
                <tr>
                    <td colspan="4" class="classification-title">{{ $grupo['clasificacion'] }}</td>
                </tr>
                @foreach($grupo['movimientos'] as $mov)
                <tr>
                    <td class="text-center">{{ $mov['folio'] }}</td>
                    <td class="text-center">{{ strtolower($mov['fecha']) }}</td>
                    <td class="text-left">{{ strtoupper($mov['observacion']) }}</td>
                    <td class="text-right">{{ number_format($mov['importe'], 2) }}</td>
                </tr>
                @endforeach
                <tr>
                    <td colspan="2"></td>
                    <td class="text-right bold" style="padding-top: 10px;">TOTAL DE {{ strtoupper($grupo['clasificacion']) }}:</td>
                    <td class="text-right bold" style="padding-top: 10px;">{{ number_format($grupo['total_clasificacion'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="header-top" style="margin-top: 20px;">
    <div class="header-left"></div>
    <div class="header-center"></div>
    <div class="header-right" style="border-top: 1px solid #000; padding-top: 5px;">
        <span class="bold">TOTAL GENERAL DE GASTOS PENDIENTES DE PAGO:</span> &nbsp;&nbsp;&nbsp;&nbsp; <span class="bold">{{ number_format($gran_total, 2) }}</span>
    </div>
</div>

</body>
</html>
