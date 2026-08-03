<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Compras Detallado</title>
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
        .footer { position: fixed; bottom: -20px; width: 100%; text-align: center; font-size: 8px; }
        .summary-box { margin-top: 20px; font-weight: bold; }
    </style>
</head>
<body>

@php
    $fechainicial = isset($filtros['fecha_inicio']) ? \Carbon\Carbon::parse($filtros['fecha_inicio'])->translatedFormat('d-M-Y') : 'N/A';
    $fechafinal = isset($filtros['fecha_fin']) ? \Carbon\Carbon::parse($filtros['fecha_fin'])->translatedFormat('d-M-Y') : 'N/A';
    $categoriaFiltro = isset($filtros['categoria']) ? $filtros['categoria'] : 'TODAS';
@endphp

<div class="header">
    <div class="title">PRESTAMO EXPRESS MATRIZ</div>
    <div>CORPORATIVO EXPRESS S.A. DE C.V.</div>
    <div>SISTEMA DE CASAS DE EMPE&Ntilde;O "SICAE"</div>
    <div>REPORTE DE COMPRAS DETALLADO</div>
    <div style="margin-top: 10px;">DEL {{ strtoupper($fechainicial) }} AL {{ strtoupper($fechafinal) }}</div>
    <div>CATEGORIA: {{ strtoupper($categoriaFiltro) }}</div>
    <div style="text-align: right; font-size: 8px;">HORA: {{ \Carbon\Carbon::now()->format('h:i a') }}</div>
</div>

<table>
    <thead>
        <tr>
            <th>FOLIO</th>
            <th>FECHA</th>
            <th>CLIENTE</th>
            <th>TIPO / KILATAJE</th>
            <th>ARTICULO</th>
            <th>CATEGORIA</th>
            <th class="text-right">PRECIO COMPRA ($)</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($compras as $compra)
            <tr>
                <td>{{ $compra->folio }}</td>
                <td>{{ \Carbon\Carbon::parse($compra->fecha)->translatedFormat('d-M-Y') }}</td>
                <td class="text-left">{{ $compra->cliente }}</td>
                <td>{{ $compra->categoria_detalle }}</td>
                <td>{{ $compra->articulo }}</td>
                <td>{{ $compra->categoria }}</td>
                <td class="text-right">{{ number_format($compra->precio_compra, 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center">No hay compras registradas en el periodo indicado.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="summary-box">
    <table>
        <tr style="background-color: #e0e0e0;">
            <td colspan="6" class="text-right bold" style="border: none;">RESUMEN DE TOTALES</td>
            <td class="text-right bold" style="border: none; border-top: 2px solid black;">{{ number_format($totales['monto_total'], 2) }}</td>
        </tr>
        <tr>
            <td colspan="6" class="text-right bold" style="border: none;">ARTICULOS COMPRADOS:</td>
            <td class="text-right bold" style="border: none;">{{ $totales['cantidad_articulos'] }}</td>
        </tr>
    </table>
</div>

<div class="footer">
    Impreso el {{ $fechaImpresion }}
</div>

</body>
</html>
