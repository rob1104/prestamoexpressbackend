<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Movimientos (Pagos Almacenaje)</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 8px; }
        .header { text-align: center; margin-bottom: 20px; font-weight: bold; }
        .title { font-size: 12px; margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 3px; }
        th { border: 1px solid #000; background-color: #e0e0e0; font-size: 7px; text-align: center; }
        td { font-size: 7px; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }
        .page-break { page-break-after: always; }
        .footer { position: fixed; bottom: -20px; width: 100%; text-align: center; font-size: 8px; }
        
        .totals-row td {
            border-top: 1px solid #000;
        }
        
        .grand-totals td {
            border-top: 2px solid #000;
        }

        .highlight-total {
            background-color: #ffff99; /* Amarillo pastel como en la imagen */
        }
    </style>
</head>
<body>

@php
    \Carbon\Carbon::setLocale('es');
    $fechainicialStr = isset($fecha_inicial) ? \Carbon\Carbon::parse($fecha_inicial)->translatedFormat('d-M-Y') : '';
    $fechafinalStr = isset($fecha_final) ? \Carbon\Carbon::parse($fecha_final)->translatedFormat('d-M-Y') : '';

    $gran_capital = 0;
    $gran_almacenaje = 0;
    $gran_interes = 0;
    $gran_iva = 0;
    $gran_total = 0;
@endphp

<div class="header">
    <div class="title">PRESTAMO EXPRESS MATRIZ</div>
    <div>CORPORATIVO EXPRESS S.A. DE C.V.</div>
    <div>SISTEMA DE CASAS DE EMPEÑO "SICAE"</div>
    <div>MOVIMIENTOS (PAGOS ALMACENAJE)</div>
    
    <table style="width: 100%; margin-top: 10px; border: none; margin-bottom: 5px;">
        <tr style="border: none;">
            <td class="text-left" style="width: 33%; border: none;">FECHA: {{ strtoupper(now()->translatedFormat('d-M-Y')) }}</td>
            <td class="text-center bold" style="width: 33%; border: none;">DEL {{ strtoupper($fechainicialStr) }} AL {{ strtoupper($fechafinalStr) }}</td>
            <td class="text-right" style="width: 33%; border: none;">HORA: {{ now()->format('h:ia') }}</td>
        </tr>
    </table>
</div>

<table>
    <thead>
        <tr>
            <th>FOLIO</th>
            <th>CLIENTE</th>
            <th>CATEGORIA</th>
            <th>CAPITAL</th>
            <th>ALMACENAJE</th>
            <th>INTERES</th>
            <th>IVA INTERES</th>
            <th>PAGO</th>
        </tr>
    </thead>
    <tbody>
        @foreach($movimientos as $fecha => $grupo)
            @php
                $fechaFormat = \Carbon\Carbon::parse($fecha)->translatedFormat('d-M-Y');
                
                $sum_capital = 0;
                $sum_almacenaje = 0;
                $sum_interes = 0;
                $sum_iva = 0;
                $sum_total = 0;
            @endphp

            <tr>
                <td colspan="8" class="text-left bold" style="padding-top: 15px; padding-bottom: 10px; font-size: 10px;">
                    MOVIMIENTOS DEL DIA: &nbsp;&nbsp;&nbsp; {{ strtolower($fechaFormat) }}
                </td>
            </tr>

            @foreach($grupo as $mov)
                @php
                    $sum_capital += $mov['capital'];
                    $sum_almacenaje += $mov['almacenaje'];
                    $sum_interes += $mov['interes'];
                    $sum_iva += $mov['iva'];
                    $sum_total += $mov['pago'];
                @endphp
                <tr>
                    <td class="text-left">{{ $mov['folio_str'] }}</td>
                    <td class="text-left">{{ $mov['cliente'] }}</td>
                    <td class="text-center">{{ $mov['categoria'] }}</td>
                    <td class="text-right">{{ number_format($mov['capital'], 2) }}</td>
                    <td class="text-right">{{ number_format($mov['almacenaje'], 2) }}</td>
                    <td class="text-right">{{ number_format($mov['interes'], 2) }}</td>
                    <td class="text-right">{{ number_format($mov['iva'], 2) }}</td>
                    <td class="text-right">{{ number_format($mov['pago'], 2) }}</td>
                </tr>
            @endforeach
            
            @php
                $gran_capital += $sum_capital;
                $gran_almacenaje += $sum_almacenaje;
                $gran_interes += $sum_interes;
                $gran_iva += $sum_iva;
                $gran_total += $sum_total;
            @endphp

            <!-- TOTAL POR DIA -->
            <tr class="totals-row">
                <td colspan="3" class="text-right bold" style="padding-top: 5px;">TOTALES DE FECHA: {{ strtolower($fechaFormat) }}</td>
                <td class="text-right bold" style="padding-top: 5px;">{{ number_format($sum_capital, 2) }}</td>
                <td class="text-right bold" style="padding-top: 5px;">{{ number_format($sum_almacenaje, 2) }}</td>
                <td class="text-right bold" style="padding-top: 5px;">{{ number_format($sum_interes, 2) }}</td>
                <td class="text-right bold" style="padding-top: 5px;">{{ number_format($sum_iva, 2) }}</td>
                <td class="text-right bold" style="padding-top: 5px;">{{ number_format($sum_total, 2) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<div style="margin-top: 20px;">
    <table style="border: none;">
        <tr class="grand-totals">
            <td class="text-right bold" style="width: 50%; padding-top: 10px;">TOTALES GENERALES:</td>
            <td class="text-right bold" style="padding-top: 10px; width: 10%;">{{ number_format($gran_capital, 2) }}</td>
            <td class="text-right bold highlight-total" style="padding-top: 10px; width: 10%;">{{ number_format($gran_almacenaje, 2) }}</td>
            <td class="text-right bold" style="padding-top: 10px; width: 10%;">{{ number_format($gran_interes, 2) }}</td>
            <td class="text-right bold" style="padding-top: 10px; width: 10%;">{{ number_format($gran_iva, 2) }}</td>
            <td class="text-right bold" style="padding-top: 10px; width: 10%;">{{ number_format($gran_total, 2) }}</td>
        </tr>
    </table>
</div>

<div class="footer">
    <!-- Footer if needed -->
</div>
</body>
</html>
