<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Boletas Vencidas</title>
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
    </style>
</head>
<body>

<div class="header">
    <h1>Reporte de Boletas Vencidas / Por Vencer</h1>
    <p>Fecha de Impresión: {{ $fechaImpresion }}</p>
    <p>
        Filtros -> 
        Fecha Corte: {{ $filtros['fecha_corte'] ?? 'Hoy' }} | 
        Días de Tolerancia: {{ $filtros['dias_tolerancia'] ?? 0 }} | 
        Estatus: {{ $filtros['estatus'] ?? 'Todos' }}
    </p>
</div>

<table>
    <thead>
        <tr>
            <th>Folio</th>
            <th>Cliente</th>
            <th>Artículo</th>
            <th>Préstamo</th>
            <th>Vencimiento</th>
            <th class="text-center">Días</th>
            <th class="text-right">Monto ($)</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($boletas as $boleta)
            <tr>
                <td>{{ $boleta['folio'] }}</td>
                <td>{{ $boleta['cliente'] }}</td>
                <td>{{ $boleta['articulo'] }}</td>
                <td>{{ $boleta['fecha_prestamo'] }}</td>
                <td>{{ $boleta['fecha_vencimiento'] }}</td>
                <td class="text-center">{{ $boleta['dias_vencido'] }}</td>
                <td class="text-right">${{ number_format($boleta['monto'], 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center">No hay boletas que coincidan con los filtros.</td>
            </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <th colspan="6" class="text-right">Total Monto:</th>
            <th class="text-right">${{ number_format($boletas->sum('monto'), 2) }}</th>
        </tr>
    </tfoot>
</table>

<div class="footer">
    Generado automáticamente por el Sistema de Control Préstamo Express
</div>

</body>
</html>
