<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Comprobante de Movimiento</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            margin: 0;
            padding: 0;
            width: 80mm;
        }
        .center {
            text-align: center;
        }
        .bold {
            font-weight: bold;
        }
        .ticket {
            padding: 5px;
        }
        .title {
            font-size: 14px;
            margin-bottom: 5px;
        }
        .divider {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        td {
            padding: 2px 0;
            vertical-align: top;
        }
        .right {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="center bold title">{{ $sucursal->nombre_sucursal ?? 'PRESTAMO EXPRESS' }}</div>
        <div class="center">{{ $sucursal->direccion ?? 'DIRECCIÓN MATRIZ' }}</div>
        <div class="center">RFC: {{ $sucursal->rfc ?? 'XAXX010101000' }}</div>
        <div class="divider"></div>
        <div class="center bold" style="font-size: 13px;">
            COMPROBANTE DE {{ $movimiento->tipo === 'ENTRADA' ? 'INGRESO' : 'RETIRO/GASTO' }}
        </div>
        <div class="divider"></div>
        <table>
            <tr>
                <td>Folio:</td>
                <td class="right bold">{{ str_pad($movimiento->id, 6, '0', STR_PAD_LEFT) }}</td>
            </tr>
            <tr>
                <td>Fecha:</td>
                <td class="right">{{ $movimiento->created_at->format('d/m/Y H:i:s') }}</td>
            </tr>
            <tr>
                <td>Cajero:</td>
                <td class="right">{{ $cajero }}</td>
            </tr>
        </table>
        <div class="divider"></div>
        <div class="bold">Concepto:</div>
        <div>{{ $movimiento->conceptoFlujo ? $movimiento->conceptoFlujo->nombre : 'MOVIMIENTO MANUAL' }}</div>
        
        @if($observacionesExtras)
        <div class="bold" style="margin-top: 5px;">Observaciones:</div>
        <div>{{ $observacionesExtras }}</div>
        @endif

        <div class="divider"></div>
        <table>
            <tr>
                <td class="bold" style="font-size: 14px;">TOTAL:</td>
                <td class="right bold" style="font-size: 14px;">${{ number_format($movimiento->monto, 2) }}</td>
            </tr>
        </table>
        <div class="divider"></div>
        <div class="center" style="margin-top: 20px;">
            ___________________________<br>
            Firma de conformidad
        </div>
        <div class="center" style="margin-top: 15px;">
            *** COPIA PARA SUCURSAL ***
        </div>
    </div>
</body>
</html>
