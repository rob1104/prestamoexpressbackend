<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas Detallado</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header p { margin: 2px 0; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background-color: #f4f4f4; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #777; }
        .summary-box { border: 1px solid #ccc; padding: 10px; margin-top: 20px; background-color: #f9f9f9; }
    </style>
</head>
<body>

<div class="header">
    <h1>Reporte de Ventas Detallado</h1>
    <p>Fecha de Impresión: {{ $fechaImpresion }}</p>
    <p>
        Filtros -> 
        Periodo: {{ $filtros['fecha_inicio'] ?? 'N/A' }} al {{ $filtros['fecha_fin'] ?? 'N/A' }} | 
        Categoría: {{ $filtros['categoria'] ?? 'Todas' }}
    </p>
</div>

<table>
    <thead>
        <tr>
            <th>Folio</th>
            <th>Fecha</th>
            <th>Cliente</th>
            <th>Artículo</th>
            <th>Categoría</th>
            <th class="text-right">Precio Venta ($)</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($ventas as $venta)
            <tr>
                <td>{{ $venta->folio }}</td>
                <td>{{ $venta->fecha }}</td>
                <td>{{ $venta->cliente }}</td>
                <td>{{ $venta->articulo }}</td>
                <td>{{ $venta->categoria }}</td>
                <td class="text-right">${{ number_format($venta->precio_venta, 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center">No hay ventas registradas en el periodo indicado.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="summary-box">
    <strong>Resumen:</strong><br>
    Artículos Vendidos: {{ $totales['cantidad_articulos'] }}<br>
    Monto Total Vendido: ${{ number_format($totales['monto_total'], 2) }}
</div>

<div class="footer">
    Generado automáticamente por el Sistema de Control Préstamo Express
</div>

</body>
</html>
