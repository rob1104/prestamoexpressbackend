<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Detalles de Movimientos</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; margin: 0; padding: 0; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2, .header h3, .header h4 { margin: 2px 0; }
        .info { width: 100%; margin-bottom: 15px; }
        .info td { font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 4px; }
        th { background-color: #f2f2f2; font-size: 10px; text-transform: uppercase; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .title-row { background-color: #e0e0e0; font-weight: bold; font-size: 11px; }
        .total-row { font-weight: bold; font-size: 12px; }
        .footer { font-size: 9px; text-align: center; position: fixed; bottom: 0; width: 100%; }
    </style>
</head>
<body>
    <div class="header">
        <h2>PRESTAMO EXPRESS MATRIZ</h2>
        <h3>CORPORATIVO EXPRESS S.A. DE C.V.</h3>
        <h4>SISTEMA DE CASAS DE EMPEÑO "SICAE"</h4>
        <h4>REPORTE DE DETALLES DE MOVIMIENTOS</h4>
    </div>

    <table class="info" style="border: none;">
        <tr style="border: none;">
            <td style="border: none;">FECHA: {{ $rangoFechas }}</td>
            <td style="border: none; text-align: right;">{{ $caja }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th style="width: 15%;">FECHA</th>
                <th style="width: 45%;">CONCEPTO / MOVIMIENTO</th>
                <th style="width: 15%;">RECIBO</th>
                <th style="width: 10%;">TIPO</th>
                <th style="width: 15%;">IMPORTE</th>
            </tr>
        </thead>
        <tbody>
            @php $granTotalEntradas = 0; $granTotalSalidas = 0; @endphp
            @foreach($movimientos as $concepto => $items)
                <tr class="title-row">
                    <td colspan="5">{{ $concepto }}</td>
                </tr>
                @php $subtotal = 0; @endphp
                @foreach($items as $item)
                    <tr>
                        <td class="text-center">{{ \Carbon\Carbon::parse($item['fecha'])->format('d-M-Y') }}</td>
                        <td>{{ $item['concepto'] }}</td>
                        <td class="text-center">{{ $item['recibo'] }}</td>
                        <td class="text-center">{{ $item['tipo'] }}</td>
                        <td class="text-right">$ {{ number_format($item['monto'], 2) }}</td>
                    </tr>
                    @php 
                        $subtotal += $item['monto'];
                        if ($item['tipo'] === 'ENTRADA') $granTotalEntradas += $item['monto'];
                        if ($item['tipo'] === 'SALIDA') $granTotalSalidas += $item['monto'];
                    @endphp
                @endforeach
                <tr class="total-row">
                    <td colspan="4" class="text-right">TOTAL {{ $concepto }}:</td>
                    <td class="text-right">$ {{ number_format($subtotal, 2) }}</td>
                </tr>
            @endforeach
            <tr class="total-row" style="background-color: #d4edda;">
                <td colspan="4" class="text-right">GRAN TOTAL ENTRADAS:</td>
                <td class="text-right">$ {{ number_format($granTotalEntradas, 2) }}</td>
            </tr>
            <tr class="total-row" style="background-color: #f8d7da;">
                <td colspan="4" class="text-right">GRAN TOTAL SALIDAS:</td>
                <td class="text-right">$ {{ number_format($granTotalSalidas, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        Impreso el {{ $fechaImpresion }}
    </div>
</body>
</html>
