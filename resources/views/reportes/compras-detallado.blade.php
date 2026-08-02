<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Compras Detallado</title>
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
    <h1>Reporte de Compras Detallado</h1>
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
            <th>Tipo / Kilataje</th>
            <th>Artículo</th>
            <th>Categoría</th>
            <th class="text-right">Precio Compra ($)</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($compras as $compra)
            <tr>
                <td>{{ $compra->folio }}</td>
                <td>{{ $compra->fecha }}</td>
                <td>{{ $compra->cliente }}</td>
                <td>{{ $compra->categoria_detalle }}</td>
                <td>{{ $compra->articulo }}</td>
                <td>{{ $compra->categoria }}</td>
                <td class="text-right">${{ number_format($compra->precio_compra, 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center">No hay compras registradas en el periodo indicado.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="summary-box">
    <strong>Resumen:</strong><br>
    Artículos Comprados: {{ $totales['cantidad_articulos'] }}<br>
    Monto Total Comprado: ${{ number_format($totales['monto_total'], 2) }}
</div>

<div class="footer">
    Generado automáticamente por el Sistema de Control Préstamo Express
</div>

</body>
</html>
