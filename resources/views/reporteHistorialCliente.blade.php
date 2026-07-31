<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Historial del Cliente</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 16px;
        }
        .header h2 {
            margin: 0;
            font-size: 12px;
            font-weight: normal;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        td, th {
            padding: 2px 4px;
            vertical-align: top;
        }
        .info-table td {
            font-size: 11px;
        }
        .section-title {
            font-weight: bold;
            text-decoration: underline;
            margin: 10px 0 5px 0;
        }
        .grid-container {
            width: 100%;
        }
        .grid-container td {
            width: 50%;
        }
        .pagos-table {
            border: 1px solid #000;
        }
        .pagos-table th {
            border-bottom: 1px solid #000;
            text-align: left;
        }
        .pagos-table td {
            border-bottom: 1px dashed #ccc;
        }
        .bold {
            font-weight: bold;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>{{ strtoupper($sucursal->razon_social ?? 'PRESTAMO EXPRESS MATRIZ') }}</h1>
        <h2>SISTEMA DE CASAS DE EMPEÑO "SICAE"</h2>
        <h2>HISTORIAL DEL CLIENTE</h2>
    </div>

    <table class="info-table">
        <tr>
            <td width="20%">FECHA:</td>
            <td width="30%" class="bold">{{ strtoupper(now()->translatedFormat('d-M-Y')) }}</td>
            <td width="20%">HORA:</td>
            <td width="30%" class="bold">{{ now()->format('h:i:s a') }}</td>
        </tr>
        <tr>
            <td>FOLIO BOLETA:</td>
            <td class="bold">{{ $boleta->id }}</td>
            <td>CLIENTE:</td>
            <td class="bold">{{ strtoupper($boleta->cliente->nombre . ' ' . $boleta->cliente->apellido_paterno . ' ' . $boleta->cliente->apellido_materno) }}</td>
        </tr>
        <tr>
            <td>IDENTIFICACION:</td>
            <td class="bold">{{ $boleta->cliente->identificacion ?? 'NO PROPORCIONA' }}</td>
            <td>PRESTAMO:</td>
            <td class="bold">${{ number_format($boleta->prestamo, 2) }}</td>
        </tr>
        <tr>
            <td>FECHA BOLETA:</td>
            <td class="bold">{{ \Carbon\Carbon::parse($boleta->fecha_boleta)->translatedFormat('d-M-Y') }}</td>
            <td>COMISION:</td>
            <td class="bold">${{ number_format($boleta->comision, 2) }}</td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td>TOTAL:</td>
            <td class="bold">${{ number_format($boleta->total_pagar, 2) }}</td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td>TIPO PRESTAMO:</td>
            <td class="bold">PRÉSTAMO {{ strtoupper($boleta->tipo_prestamo) }}</td>
        </tr>
    </table>

    <div class="section-title">DESCRIPCION:</div>
    <div>
        @foreach ($boleta->partidas as $d)
            {{ $d->gramos_cantidad }}{{ $d->tipo == 'moneda' ? ' pzs' : ' gr' }} {{ strtoupper($d->subtipo) }} {{ strtoupper($d->descripcion) }}<br>
        @endforeach
    </div>

    <br>

    <table class="grid-container">
        <tr>
            <td style="padding-right: 10px;">
                <div class="section-title text-center" style="text-align: center;">TRADICIONAL</div>
                <table style="width: 100%; margin: 0 auto; font-size: 10px;">
                    <tr>
                        <td>Prestamos Tradicionales..:</td>
                        <td align="right">{{ $stats['tradicional']['prestamos'] }}</td>
                        <td align="right">${{ number_format($stats['tradicional']['importe'], 2) }}</td>
                    </tr>
                    <tr>
                        <td>Boletas Refrendadas......:</td>
                        <td align="right">{{ $stats['tradicional']['refrendadas'] }}</td>
                        <td align="right"></td>
                    </tr>
                    <tr>
                        <td>Boletas Desempeñadas.....:</td>
                        <td align="right">{{ $stats['tradicional']['desempenadas'] }}</td>
                        <td align="right"></td>
                    </tr>
                    <tr>
                        <td>Adjudicaciones Reales....:</td>
                        <td align="right">{{ $stats['tradicional']['adjudicaciones'] }}</td>
                        <td align="right"></td>
                    </tr>
                    <tr>
                        <td>Adjudicaciones Fisicas...:</td>
                        <td align="right">0</td>
                        <td align="right"></td>
                    </tr>
                    <tr>
                        <td>En Comercializacion......:</td>
                        <td align="right">{{ $stats['tradicional']['en_comercializacion'] }}</td>
                        <td align="right"></td>
                    </tr>
                    <tr>
                        <td>Boletas Recuperadas......:</td>
                        <td align="right">0</td>
                        <td align="right"></td>
                    </tr>
                    <tr>
                        <td class="bold">Total de Préstamos Vig.:</td>
                        <td align="right" class="bold">{{ $stats['tradicional']['vigentes'] }}</td>
                        <td align="right"></td>
                    </tr>
                    <tr>
                        <td colspan="3" align="center" class="bold" style="padding-top: 10px;">* Historial de Pagos *</td>
                    </tr>
                    <tr>
                        <td>Total Pagado.............:</td>
                        <td align="right"></td>
                        <td align="right">${{ number_format($stats['tradicional']['total_pagado'], 2) }}</td>
                    </tr>
                    <tr>
                        <td>Recargos.................:</td>
                        <td align="right"></td>
                        <td align="right">${{ number_format($stats['tradicional']['recargos'], 2) }}</td>
                    </tr>
                    <tr>
                        <td>Extemporáneos............:</td>
                        <td align="right"></td>
                        <td align="right">${{ number_format(0, 2) }}</td>
                    </tr>
                </table>
            </td>
            <td style="padding-left: 10px;">
                <div class="section-title text-center" style="text-align: center;">PAGOS</div>
                <table style="width: 100%; margin: 0 auto; font-size: 10px;">
                    <tr>
                        <td>Prestamos en Pagos.......:</td>
                        <td align="right">{{ $stats['pagos']['prestamos'] }}</td>
                        <td align="right">${{ number_format($stats['pagos']['importe'], 2) }}</td>
                    </tr>
                    <tr>
                        <td>Préstamos Terminados.....:</td>
                        <td align="right">{{ $stats['pagos']['terminados'] }}</td>
                        <td align="right"></td>
                    </tr>
                    <tr>
                        <td class="bold">Pagos Vigentes...........:</td>
                        <td align="right" class="bold">{{ $stats['pagos']['vigentes'] }}</td>
                        <td align="right"></td>
                    </tr>
                    <tr>
                        <td>Adjudicaciones...........:</td>
                        <td align="right">0</td>
                        <td align="right"></td>
                    </tr>
                    <tr>
                        <td>En Comercialización......:</td>
                        <td align="right">0</td>
                        <td align="right"></td>
                    </tr>
                    <tr>
                        <td>Boletas Recuperadas......:</td>
                        <td align="right">0</td>
                        <td align="right"></td>
                    </tr>
                    <tr><td colspan="3" style="padding-top:20px;"></td></tr>
                    <tr>
                        <td colspan="3" align="center" class="bold" style="padding-top: 10px;">* Historial de Pagos *</td>
                    </tr>
                    <tr>
                        <td>Total Pagado.............:</td>
                        <td align="right"></td>
                        <td align="right">${{ number_format($stats['pagos']['total_pagado'], 2) }}</td>
                    </tr>
                    <tr>
                        <td>Recargos.................:</td>
                        <td align="right"></td>
                        <td align="right">${{ number_format($stats['pagos']['recargos'], 2) }}</td>
                    </tr>
                    <tr>
                        <td>Extemporáneos............:</td>
                        <td align="right"></td>
                        <td align="right">${{ number_format(0, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="section-title text-center" style="text-align: center; margin-top: 20px;">PAGOS REALIZADOS</div>
    <table class="pagos-table">
        <thead>
            <tr>
                <th>NO. PAGO</th>
                <th>FECHA VENCE</th>
                <th>PAGO A REALIZAR</th>
                <th>FECHA PAGO</th>
                <th>PAGO REALIZADO</th>
                <th>ESTATUS</th>
                <th>RECARGOS</th>
                <th>USUARIO</th>
            </tr>
        </thead>
        <tbody>
            @php $pagosRel = $boleta->getRelation('pagos'); @endphp
            @foreach($pagosRel as $index => $pago)
            <tr>
                <td>{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
                <td>{{ \Carbon\Carbon::parse($pago->fecha)->translatedFormat('d-M-Y') }}</td>
                <td>${{ number_format($pago->totalPagado, 2) }}</td>
                <td>{{ \Carbon\Carbon::parse($pago->created_at)->translatedFormat('d-M-Y') }}</td>
                <td>${{ number_format($pago->totalPagado, 2) }}</td>
                <td>{{ strtoupper(substr($pago->tipo_movimiento ?? 'PAGO', 0, 2)) }}</td>
                <td>${{ number_format($pago->recargosNormal ?? 0, 2) }}</td>
                <td>{{ strtoupper($pago->user->name ?? 'SISTEMA') }}</td>
            </tr>
            @endforeach
            @if(empty($pagosRel) || $pagosRel->count() == 0)
            <tr>
                <td colspan="8" align="center">NO HAY PAGOS REALIZADOS</td>
            </tr>
            @endif
        </tbody>
    </table>

</body>
</html>
