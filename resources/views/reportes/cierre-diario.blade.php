<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Cierre de Caja Diario</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 16px; }
        .header p { margin: 2px 0; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 4px; text-align: left; }
        th { background-color: #f4f4f4; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #777; }
        .summary-box { border: 1px solid #ccc; padding: 10px; margin-top: 20px; background-color: #f9f9f9; }
    </style>
</head>
<body>

<div class="header">
    <h1>Reporte de Cierre de Caja Diario</h1>
    <p>Fecha de Impresión: {{ $fechaImpresion }}</p>
    <p>
        Filtros -> 
        Periodo: {{ $filtros['fecha_inicio'] ?? 'N/A' }} al {{ $filtros['fecha_fin'] ?? 'N/A' }}
    </p>
</div>

<table>
    <thead>
        <tr>
            <th>Fecha</th>
            <th class="text-right">Prest. Nuevos</th>
            <th class="text-right">Cap. Recuperado</th>
            <th class="text-right">Int. Recuperado</th>
            <th class="text-right">Recargos</th>
            <th class="text-right">V. Joyería</th>
            <th class="text-right">V. Electrónicos</th>
            <th class="text-right">Entradas</th>
            <th class="text-right">Salidas</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($cierres as $cierre)
            <tr>
                <td>{{ $cierre->fecha_cierre }}</td>
                <td class="text-right">${{ number_format($cierre->prestamos_nuevos, 2) }}</td>
                <td class="text-right">${{ number_format($cierre->capital_recuperado, 2) }}</td>
                <td class="text-right">${{ number_format($cierre->interes_recuperado, 2) }}</td>
                <td class="text-right">${{ number_format($cierre->recargos_cobrados, 2) }}</td>
                <td class="text-right">${{ number_format($cierre->ventas_joyeria, 2) }}</td>
                <td class="text-right">${{ number_format($cierre->ventas_electronicos, 2) }}</td>
                <td class="text-right">${{ number_format($cierre->entradas_otros, 2) }}</td>
                <td class="text-right">${{ number_format($cierre->salidas_otros, 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="9" class="text-center">No hay cierres registrados en el periodo indicado.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="summary-box">
    <strong>Resumen Total del Periodo:</strong><br><br>
    Préstamos Nuevos: ${{ number_format($totales['prestamos_nuevos'], 2) }}<br>
    Capital Recuperado: ${{ number_format($totales['capital_recuperado'], 2) }}<br>
    Interés Recuperado: ${{ number_format($totales['interes_recuperado'], 2) }}<br>
    Recargos Cobrados: ${{ number_format($totales['recargos_cobrados'], 2) }}<br>
    Ventas Joyería: ${{ number_format($totales['ventas_joyeria'], 2) }}<br>
    Ventas Electrónicos: ${{ number_format($totales['ventas_electronicos'], 2) }}<br>
    Entradas Otros: ${{ number_format($totales['entradas_otros'], 2) }}<br>
    Salidas Otros: ${{ number_format($totales['salidas_otros'], 2) }}<br>
</div>

<div class="footer">
    Generado automáticamente por el Sistema de Control Préstamo Express
</div>

</body>
</html>
