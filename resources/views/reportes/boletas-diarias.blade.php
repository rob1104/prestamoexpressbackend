<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Relación de Boletas Diarias para Depósito</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 8px; }
        .header { text-align: center; margin-bottom: 20px; font-weight: bold; }
        .title { font-size: 12px; margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 3px; text-align: center; }
        th { background-color: #e0e0e0; font-size: 7px; }
        td { font-size: 7px; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .bold { font-weight: bold; }
        .page-break { page-break-after: always; }
        .footer { position: fixed; bottom: -20px; width: 100%; text-align: center; font-size: 8px; }
    </style>
</head>
<body>

@php
    $fechainicial = isset($filtros['fecha_inicial']) ? \Carbon\Carbon::parse($filtros['fecha_inicial'])->translatedFormat('d-M-Y') : '';
    $fechafinal = isset($filtros['fecha_final']) ? \Carbon\Carbon::parse($filtros['fecha_final'])->translatedFormat('d-M-Y') : '';
    $tipoReporte = isset($filtros['tipo_reporte']) ? $filtros['tipo_reporte'] : 'desempenos';

    $tituloReporte = 'RELACION DE BOLETAS DIARIAS PARA DEPOSITO';
    if ($tipoReporte === 'pagos') {
        $tituloReporte = 'RELACION DE PAGOS PARA DEPOSITO';
    } elseif ($tipoReporte === 'refrendos') {
        $tituloReporte = 'RELACION DE REFRENDOS PARA ALMACENAJE';
    }

    // Agrupar boletas por fecha
    $boletasAgrupadas = $boletas->groupBy('fecha');
    
    // Totales Generales
    $gran_capital = 0;
    $gran_interes = 0;
    $gran_custodia = 0;
    $gran_admin = 0;
    $gran_iva = 0;
    $gran_interes_iva = 0;
    $gran_total = 0;
@endphp

<div class="header">
    <div class="title">PRESTAMO EXPRESS MATRIZ</div>
    <div>CORPORATIVO EXPRESS S.A. DE C.V.</div>
    <div>SISTEMA DE CASAS DE EMPEÑO "SICAE"</div>
    <div>{{ $tituloReporte }}</div>
    <div style="margin-top: 10px;">DEL {{ strtoupper($fechainicial) }} AL {{ strtoupper($fechafinal) }}</div>
    <div style="text-align: right; font-size: 8px;">HORA: {{ \Carbon\Carbon::now()->format('h:i a') }}</div>
</div>

@foreach($boletasAgrupadas as $fecha => $grupoBoletas)
    @php
        $sum_capital = 0;
        $sum_interes = 0;
        $sum_custodia = 0;
        $sum_admin = 0;
        $sum_iva = 0;
        $sum_interes_iva = 0;
        $sum_total = 0;
    @endphp

    <table>
        <thead>
            <tr>
                <th>FOLIO</th>
                <th>PAG</th>
                <th>NO. CLIENTE</th>
                <th>CLIENTE</th>
                <th>CAPITAL</th>
                <th>INTERES</th>
                <th>CUSTODIA</th>
                <th>ADMINISTRACION</th>
                <th>IVA</th>
                <th>INTERES + IVA</th>
                <th>TOTAL</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="11" class="text-left bold" style="background-color: #f9f9f9;">
                    FECHA DE MOVIMIENTO: &nbsp;&nbsp;&nbsp; {{ \Carbon\Carbon::parse($fecha)->translatedFormat('d-M-Y') }}
                </td>
            </tr>
            @foreach($grupoBoletas as $b)
                @php
                    $sum_capital += $b['capital'];
                    $sum_interes += $b['interes'];
                    $sum_custodia += $b['custodia'];
                    $sum_admin += $b['administracion'];
                    $sum_iva += $b['iva'];
                    $sum_interes_iva += $b['interes_iva'];
                    $sum_total += $b['total'];
                @endphp
                <tr>
                    <td>{{ $b['folio'] }}</td>
                    <td>{{ $b['pag'] }}</td>
                    <td>{{ $b['no_cliente'] }}</td>
                    <td class="text-left">{{ $b['cliente'] }}</td>
                    <td class="text-right">{{ number_format($b['capital'], 2) }}</td>
                    <td class="text-right">{{ number_format($b['interes'], 2) }}</td>
                    <td class="text-right">{{ number_format($b['custodia'], 2) }}</td>
                    <td class="text-right">{{ number_format($b['administracion'], 2) }}</td>
                    <td class="text-right">{{ number_format($b['iva'], 2) }}</td>
                    <td class="text-right">{{ number_format($b['interes_iva'], 2) }}</td>
                    <td class="text-right bold">{{ number_format($b['total'], 2) }}</td>
                </tr>
            @endforeach
            
            @php
                $gran_capital += $sum_capital;
                $gran_interes += $sum_interes;
                $gran_custodia += $sum_custodia;
                $gran_admin += $sum_admin;
                $gran_iva += $sum_iva;
                $gran_interes_iva += $sum_interes_iva;
                $gran_total += $sum_total;
            @endphp

            <!-- TOTAL POR DIA -->
            <tr style="background-color: #f5f5f5;">
                <td colspan="4" class="text-right bold">TOTAL DEL {{ \Carbon\Carbon::parse($fecha)->translatedFormat('d-M-Y') }}</td>
                <td class="text-right bold">{{ number_format($sum_capital, 2) }}</td>
                <td class="text-right bold">{{ number_format($sum_interes, 2) }}</td>
                <td class="text-right bold">{{ number_format($sum_custodia, 2) }}</td>
                <td class="text-right bold">{{ number_format($sum_admin, 2) }}</td>
                <td class="text-right bold">{{ number_format($sum_iva, 2) }}</td>
                <td class="text-right bold">{{ number_format($sum_interes_iva, 2) }}</td>
                <td class="text-right bold">{{ number_format($sum_total, 2) }}</td>
            </tr>
        </tbody>
    </table>
@endforeach

<div style="margin-top: 20px;">
    <table>
        <tr style="background-color: #e0e0e0;">
            <td colspan="4" class="text-right bold" style="border: none;">TOTALES</td>
            <td class="text-right bold" style="border: none; border-top: 2px solid black;">{{ number_format($gran_capital, 2) }}</td>
            <td class="text-right bold" style="border: none; border-top: 2px solid black;">{{ number_format($gran_interes, 2) }}</td>
            <td class="text-right bold" style="border: none; border-top: 2px solid black;">{{ number_format($gran_custodia, 2) }}</td>
            <td class="text-right bold" style="border: none; border-top: 2px solid black;">{{ number_format($gran_admin, 2) }}</td>
            <td class="text-right bold" style="border: none; border-top: 2px solid black;">{{ number_format($gran_iva, 2) }}</td>
            <td class="text-right bold" style="border: none; border-top: 2px solid black;">{{ number_format($gran_interes_iva, 2) }}</td>
            <td class="text-right bold" style="border: none; border-top: 2px solid black;">{{ number_format($gran_total, 2) }}</td>
        </tr>
    </table>
</div>

<div class="footer">
    Impreso el {{ $fechaImpresion }}
</div>
</body>
</html>
