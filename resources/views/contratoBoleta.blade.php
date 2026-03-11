<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <title>Contrato de Mutuo</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', serif; }
        @page { size: 8.5in 14in; margin: 0.2in; }
        .nTexto { font-size: 10px; text-align: justify }
        th, .normalTd { border: 1px solid black; vertical-align: middle; text-align: center; padding-left: 3px; padding-right: 3px;}
        th { background-color: #C0C0C0}
        .tTexto { font-size: 11px; text-align: center }
        .page_break { page-break-before: always; }
        .aTexto { font-size:  7px;}
        .text-center {
            text-align: center !important;
        }
        .fw-bold {
            font-weight: bold !important;
        }
        .text-decoration-underline {
            text-decoration: underline !important;
        }
    </style>
</head>
<body>
@php
    /* FECHAS Y CONVERSIONES */
    use Luecano\NumeroALetras\NumeroALetras;
    $formateador = new NumeroALetras();
    $formateador->conector = 'PESOS';
    $prestamoLetra = $formateador->toInvoice($boleta->prestamo, 2, 'M.N.' );

    $fechac = new \Carbon\Carbon($boleta->fecha_boleta);
    $fechav = new \Carbon\Carbon($boleta->fecha_vencimiento);
    $fechaa = new \Carbon\Carbon($fechaAdhesion ?? now());

    $fechaCelebracion = $fechac->translatedFormat('d F Y');
    $fechaVencimiento = $fechav->translatedFormat('d F Y');
    $fechaAdhesion = $fechaa->translatedFormat('d \d\e F \d\e Y');

    // Determinar el nombre del periodo
    $nombrePeriodo = 'Mensual';
    if ($boleta->periodo_id == 1) $nombrePeriodo = 'Semanal';
    if ($boleta->periodo_id == 2) $nombrePeriodo = 'Catorcenal';
    if ($boleta->periodo_id == 3) $nombrePeriodo = 'Quincenal';

    // Cálculos informativos (CAT e Interés Anual)
    $interesAnual = $boleta->p_interes * 12;
    $cat = $interesAnual * 1.16; // Aproximación del CAT con IVA
@endphp
<div class="nTexto fw-bold mb-1">Fecha de celebración del contrato: <span>H. MATAMOROS, TAMP. A</span>
    <span class="text-decoration-underline">
                &nbsp;&nbsp;&nbsp;&nbsp;{{strtoupper($fechaCelebracion)}}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            </span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    FOLIO No. <span class="text-decoration-underline fs-5" style="color: red">&nbsp;&nbsp;&nbsp;{{$boleta->id}}&nbsp;&nbsp;&nbsp;</span>
</div>
<div class="nTexto fw-bold" style="line-height: 14px">
    CONTRATO DE MUTUO CON INTERÉS Y GARANTÍA PRENDARIA (PRÉSTAMO), que celebran: <span>{{$sucursal->razon_social}},</span>
    EL PROVEEDOR, con domicilio <span>{{$sucursal->calle_num}}, {{$sucursal->colonia}}</span> <span>{{$sucursal->municipio}}</span>, <span>{{$sucursal->estado}}</span>, MEXICO. C.P. <span>{{$sucursal->codigo_postal}}</span>, RFC: <span>{{$sucursal->rfc}}</span>,
    Tel: <span>{{$sucursal->telefono_1}}</span>, Correo: <span>{{$sucursal->email}}</span>, en caso de persona moral: representado por : Martha Edith Muñéz Garza y EL
    CONSUMIDOR <span>{{$boleta->cliente->nombre}}</span>, que se identifica con INE número: <span>{{$boleta->cliente->identificacion ?? 'NO PROPORCIONA'}}</span>, con domicilio
    <span>{{$boleta->cliente->direccion ?? 'NO PROPORCIONA'}} {{$boleta->cliente->colonia ?? ''}}</span> con No. de teléfono: <span>{{$boleta->cliente->telefono1 ?? 'NO PROPORCIONA'}}</span> y con correo: <span>{{$boleta->cliente->email ?? 'NO PROPORCIONA'}}</span>.
    Quién designa como cotitular a <span>{{$boleta->cliente->cotitular ?? 'NO PROPORCIONA'}}</span>; y
    beneficiario a <span>{{$boleta->cliente->beneficiario ?? 'NO PROPORCIONA'}}</span> solo para efectos de este contrato.
</div>
<table class="table fw-bold mt-2 tTexto">
    <thead>
    <tr>
        <th scope="col" rowspan="2" style="width: 15%"><div>CAT</div><div>Costo Anual Total</div></th>
        <th scope="col" rowspan="2" style="width: 12%">TASA DE INTERÉS ANUAL</th>
        <th scope="col" rowspan="2" style="width: 23%">MONTO DEL PRESTAMO (MUTUO)</th>
        <th scope="col" rowspan="2" style="width: 18%">MONTO TOTAL A PAGAR</th>
        <th scope="col" style="width: 32%">COMISIONES </th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td class="normalTd" style="background-color: #C0C0C0">Montos y Cláusulas</td>
    </tr>
    <tr>
        <td class="normalTd">Para fines informativos y de comparación <span>{{number_format($cat, 2)}}</span>% FIJO Sin IVA</td>
        <td class="normalTd">
            <div><span>{{number_format($interesAnual, 2)}}</span>%</div>
            <div>TASA FIJA</div>
        </td>
        <td class="normalTd">
            <div>$<span>{{number_format($boleta->prestamo, 2, '.', ',')}}</span></div>
            <div>Moneda Nacional</div>
        </td>
        <td class="normalTd">
            <div>$<span>{{number_format($boleta->total_pagar, 2, '.', ',')}}</span></div>
            <div>Estimado al plazo máximo de desempeño o refrendo</div>
        </td>
        <td class="normalTd" style="text-align: left">
            <div>Comisión por Almacenaje: $ % [Claus 11 a]</div>
            <div>Comisión por Avalúo: $<u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u> [Claus 11 b]</div>
            <div>Comisión por Comercialización: % [Claus 11 c]</div>
            <div>Comisión por reposición de contrato: $<span>10.00</span> [Claus 11 d]</div>
            <div>Desempeño Extemporáneo: % [Claus 11 e]</div>
            <div>Gastos de administración: $<span>{{number_format($boleta->administracion ?? 0, 2, '.', ',')}}</span> [Claus 11 f]</div>
        </td>
    </tr>
    <tr>
        <td class="normalTd" colspan="5" style="text-align: left">
            Metodología de cálculo de interés: Tasa de interés anual fija dividida entre 360 días por el importe de saldo insoluto del préstamo
            por el número de días efectivamente transcurridos.
        </td>
    </tr>
    <tr>
        <td class="normalTd" colspan="5" style="text-align: left">
            Plazo del préstamos:
            <span style="text-decoration: underline;">
                            @if ($boleta->tipo_prestamo == 'pagos')
                    <span>{{$boleta->meses}} MESES</span>
                @else
                    <span>1 MES</span>
                @endif
                        </span>. Total de refrendos aplicables
            <span class="text-decoration-underline">
                            @if ($boleta->tipo_prestamo == 'pagos')
                    <span>{{$boleta->numero_pagos}}</span>
                @else
                    <span>1</span>
                @endif
                        </span>.
            Su pago será
            <span style="text-decoration: underline;">
                            <span>{{ strtoupper($nombrePeriodo) }}</span>
                        </span>. Métodos del pago aceptado: Efectivo. En caso de que el vencimiento sea en
            un día inhábil, se considerará el día hábil siguiente.
        </td>
    </tr>
    <tr>
        <td style="border: 1px solid black;" colspan="5">
            <table  class="table mb-0" style="position: relative; left: 25px">
                <tr>
                    <td class="normalTd" style="border-width: 1px; width: 10%; text-align: left" rowspan="4">OPCIONES DE PAGO PARA REFRENDO O DESEMPEÑO</td>
                    <td class="normalTd" style="border-width: 1px" rowspan="3">NÚMERO</td>
                    <td class="normalTd" style="border-width: 1px" colspan="4">MONTO</td>
                    <td class="normalTd" style="border-width: 1px" colspan="2">TOTAL A PAGAR</td>
                    <td class="normalTd" style="border-width: 1px" rowspan="3">CUÁNDO SE REALIZAN LOS PAGOS</td>
                </tr>
                <tr>
                    <td class="normalTd" rowspan="2">IMPORTE DEL MUTUO</td>
                    <td class="normalTd" rowspan="2">INTERÉS</td>
                    <td class="normalTd" rowspan="2">ALMACENAJE</td>
                    <td class="normalTd" rowspan="2">IVA</td>
                    <td class="normalTd" style="background-color: #C0C0C0" rowspan="2">POR REFRENDO</td>
                    <td class="normalTd" style="background-color: #C0C0C0" rowspan="2">POR DESEMPEÑO</td>
                </tr>
                <tr></tr>
                <tr>
                    <td class="normalTd" style="border-width: 1px">{{$boleta->meses ?? 1}}</td>
                    <td class="normalTd" style="border-width: 1px">$<span>{{number_format($boleta->prestamo, 2, '.', ',')}}</span></td>
                    <td class="normalTd" style="border-width: 1px">$ <span>{{number_format($boleta->comision, 2, '.', ',')}}</span></td>
                    <td class="normalTd" style="border-width: 1px">$ <span>{{number_format($boleta->almacenaje ?? 0, 2, '.', ',')}}</span></td>
                    <td class="normalTd" style="border-width: 1px">$ <span>{{number_format($boleta->iva_comision, 2, '.', ',')}}</span></td>
                    <td class="normalTd" style="border-width: 1px">$ <span>{{number_format($boleta->comision + $boleta->iva_comision, 2, '.', ',')}}</span></td>
                    <td class="normalTd" style="border-width: 1px">$ <span>{{number_format($boleta->total_pagar, 2, '.', ',')}}</span></td>
                    <td class="normalTd" style="border-width: 1px"><span>{{strtoupper($fechaVencimiento)}}</span></td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td style="border: 1px solid black;" colspan="5">
            <table class="table mb-0">
                <tr>
                    <td class="normalTd" style="border-width: 1px; background-color: #C0C0C0">COSTO MENSUAL TOTAL</td>
                    <td class="normalTd" style="border-width: 1px; background-color: #C0C0C0">COSTO DIARIO TOTAL</td>
                </tr>
                <tr>
                    <td class="normalTd" style="border-width: 1px;">Para fines informativos y de comparación: <span>{{number_format($cat / 12, 2, '.')}}</span>% FIJO Sin IVA</td>
                    <td class="normalTd" style="border-width: 1px;">Para fines informativos y de comparación: <span>{{number_format($cat / 360, 2, '.')}}</span> % FIJO Sin IVA</td>
                </tr>
                <tr>
                    <td class="normalTd" style="border-width: 1px;" colspan="2">
                        Cuide su capacidad de pago, generalmente no debe exceder del 35% de sus Ingresos. Si usted no paga en tempo y forma corre el riesgo de perder sus prendas.
                    </td>
                </tr>
                <tr>
                    <td class="normalTd" colspan="2">
                        GARANTÍA: Para garantizar el pago de este préstamo, el consumidor deja en garanta el bien que se describe a continuación:
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td style="border: 1px solid black;" colspan="5">
            <table class="table mb-0">
                <tr>
                    <td class="normalTd" style="border-width: 1px; background-color: #C0C0C0" colspan="5">DESCRIPCIÓN DE LA PRENDA</td>
                </tr>
                <tr>
                    <td class="normalTd" style="border-width: 1px; background-color: #C0C0C0">TIPO DE PRENDA</td>
                    <td class="normalTd" style="background-color: #C0C0C0">CARACTERÍSTICAS</td>
                    <td class="normalTd" style="background-color: #C0C0C0">AVALÚO</td>
                    <td class="normalTd" style="background-color: #C0C0C0">PRÉSTAMO</td>
                    <td class="normalTd" style="border-width: 1px; background-color: #C0C0C0">% PRÉSTAMO SOBRE AVALÚO</td>
                </tr>
                <tr>
                    <td class="normalTd" style="border-width: 1px;" colspan="2">
                        @foreach ($boleta->partidas as $d)
                            <div style="text-align: left; padding-left: 5px;">
                                <b>{{ strtoupper($d->tipo) }}:</b> {{ strtoupper($d->descripcion) }}
                            </div>
                        @endforeach
                    </td>
                    <td class="normalTd" style="border-width: 1px;">${{number_format($boleta->prestamo, 2, '.', ',')}}</td>
                    <td class="normalTd" style="border-width: 1px;">${{number_format($boleta->prestamo, 2, '.', ',')}}</td>
                    <td class="normalTd">100%</td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td class="normalTd" colspan="2">Monto del Avalúo:</td>
        <td class="normalTd" colspan="3">$<span>{{number_format($boleta->prestamo, 2, '.', ',')}}</span> &nbsp;&nbsp;&nbsp; Letra: (<span>{{$prestamoLetra}}</span>)</td>
    </tr>
    <tr>
        <td class="normalTd" colspan="2">Porcentaje del préstamo sobre el avalúo:</td>
        <td class="normalTd" colspan="3">100%</td>
    </tr>
    <tr>
        <td class="normalTd" colspan="2">El monto del préstamo se realizará en <span class="text-decoration-underline">EFECTIVO</span>:</td>
        <td class="normalTd" colspan="3">
            Efectivo o a la cuenta bancaria del Consumidor al número
            <span class="text-decoration-underline">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>,
            de la institución financiera<span class="text-decoration-underline">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>.
        </td>
    </tr>
    <tr>
        <td class="normalTd" colspan="2">Fecha límite del finiquito: <span>{{strtoupper($fechaVencimiento)}}</span></td>
        <td class="normalTd" colspan="3">Términos y condiciones para recibir pagos anticipados: cláusula 13 (décimo Tercera, Iniciso b)</td>
    </tr>
    <tr>
        <td class="normalTd" colspan="5">Estos conceptos causarán el pago del Impuesto al valor agregado (IVA) a la tasa del 16 %</td>
    </tr>
    <tr>
        <td class="normalTd" colspan="5">*El procedimiento para desempeño, refrendo, finiquito y reclamo del remanente se encuentra descrito en el contrato.</td>
    </tr>
    <tr>
        <td class="normalTd" colspan="5">
            Dudas, aclaraciones y reclamaciones:
            * Para cualquier duda, aclaración o reclamación, favor de dirigirse a: {{$sucursal->calle_num, $sucursal->colonia}}, CP {{$sucursal->codigo_postal}}, {{$sucursal->municipio}},
            {{$sucursal->estado}}. Telefono: {{$sucursal->telefono_1}}, correo electrónico: {{$sucursal->email}}; Horario: {{$sucursal->horario_atencion}}.
            <br>
            <div>* O en su caso a PROFECO a los teléfonos: 55 68 8722 o al 800 468 8722, Página de internet: www.gob.mx/profeco</div>
        </td>
    </tr>
    <tr>
        <td class="normalTd" colspan="5">
            Estado de cuenta/consulta de movimientos: (NO APLICA) o Consulta en tel: {{$sucursal->telefono_1}} o sucursal.
        </td>
    </tr>
    <tr>
        <td class="normalTd" style="background-color: #C0C0C0" colspan="5">
            Contrato de Adhesión registrado en el Registro Público de Contratos de Adhesión de la Procuraduria Federal del Consumidor, bajo el número {{$sucursal->adhesion_num}} de
            fecha {{$fechaAdhesion}}. El proveedor tene la oblicación de entregar al consumidor el documento en el cual se señala la descripción del préstamo,
            saldos, movimientos y la descripción de la Prenda en garantía.
        </td>
    </tr>
    <tr>
        <td class="normalTd" colspan="3">DESEMPEÑO</td>
        <td class="normalTd" colspan="2">FIRMAS</td>
    </tr>
    <tr>
        <td class="normalTd" colspan="3">
            <div class="nTexto">EL CONSUMIDOR recoge en el acto y a su entera satsfacción la(s) prenda(s)
                arriba descrita(s), por lo que otorga a <span>{{$sucursal->razon_social}}</span>, el finiquito
                mas amplio que en derecho corresponda, liberándolo de cualquier
                responsabilidad jurídica que hubiere surgido o pudiese surgir en relación al
                contrato y a la prenda.</div>
            <div>FECHA: <span>{{strtoupper($fechaVencimiento)}}</span></div>
        </td>
        <td class="normalTd" colspan="2">
            <div class="mb-5">Fecha: <span>{{strtoupper($fechaVencimiento)}}</span></div>
            <br>
            <br>
            <div><span class="text-decoration-underline">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></div>
            <div>EL CONSUMIDOR</div>
        </td>
    </tr>
    <tr>
        <td class="normalTd"><div></div><div class="mt-4">EL CONSUMIDOR</div></td>
        <td class="normalTd" colspan="2"><div></div><div class="mt-4">EL PROVEEDOR</div></td>
        <td class="normalTd" colspan="2"><div>(Nombre o Clave)</div><div class="mt-2">EL VALUADOR</div></td>
    </tr>
    </tbody>
</table>
<div class="fw-bold nTexto">
    EL HORARIO DE SERVICIO AL PÚBLICO EN ESTE ESTABLECIMIENTO ES DE: {{ $sucursal->horario_atencion }}
    Para todo lo relativo a la interpretación, aplicación y cumplimiento del contrato, LAS PARTES acuerdan someterse en la via administrativa a la
    Procuraduría Federal del Consumidor, y en caso de subsistr diferencias, a la jurisdicción de los tribunales competentes del lugar donde se celebra este Contrato.
</div>

<div class="page_break"></div>
<div>
    <table class="table" style="text-align: justify">
        <tr class="aTexto lh-1">
            <td style="width: 50%; vertical-align: top; padding-top: 0;">
                <div style="text-align: justify">
                    CONTRATO DE MUTUO CON INTERÉS Y GARANTÍA PRENDARIA (PRÉSTAMO) QUE CELEBRAN POR UNA PARTE {{$sucursal->nombre}},
                    CUYO NOMBRE APARECE EN EL RUBRO DE LA CARÁTULA Y POR LA OTRA, LA PERSONA CUYO NOMBRE Y DOMICILIO APARECE EN LA CARÁTULA,
                    A QUIEN EN LO SUCESIVO Y PARA EFECTOS DEL PRESENTE CONTRATO SE LE DENOMINARÁ “EL CONSUMIDOR”; A QUIENES DE MANERA CONJUNTA
                    SE LES DENOMINARÁ COMO “LAS PARTES”, AL TENOR DEL SIGUIENTE GLOSARIO, ASÍ COMO DE LAS SIGUIENTES DECLARACIONES Y CLÁUSULAS:
                </div>
                <div class="text-center fw-bold">GLOSARIO</div>
                <div>a) <b>Beneficiario</b>: Persona designada, en su caso, por el consumidor para recibir los beneficios derivados del presente contrato.</div>
                <div>b) <b>Carátula</b>: Documento en el cual se enlistan las características y costos de la operación, así como las advertencias de costos de las tasas de
                    interés y comisiones objeto del presente contrato.</div>
                <div>c) <b>Consumidor</b>: A la persona física o moral que adquiere los derechos y obligaciones establecidos en este contrato.</div>
                <div>d) <b>Cotitular</b>: Persona designada, en su caso, por el consumidor para realizar en su nombre y representación las operaciones señaladas en el
                    presente contrato.</div>
                <div>e) <b>Gastos de administración</b>: Los gastos asociados a la operación de empeño.</div>
                <div>f) <b>Proveedor</b>: Persona física o sociedad mercantil no regulada por leyes y autoridades financieras que en forma habitual o profesional realicen
                    u oferten al público contrataciones u operaciones de mutuo con interés y garanta prendaria.</div>
                <div>g) <b>Prenda</b>: Bien mueble en el comercio de procedencia lícita entregado material o jurídicamente por “EL CONSUMIDOR” a “EL PROVEEDOR”
                    para garantizar el pago del préstamo.</div>
                <div>h) <b>Refrendo</b>: Es el acto mediante el cual “EL CONSUMIDOR” cumpliendo con el pago de sus intereses y accesorios efectivamente devengados
                    y de acuerdo a lo pactado en el presente contrato recibe un nuevo plazo para el pago.</div>
                <div class="text-center fw-bold">D E C L A R A C I O N E S</div>
                <b>I.- Declara “EL PROVEEDOR”:</b>
                <div>A) Ser una persona moral legalmente constituida conforme a las leyes mexicanas, lo que se acredita con el testimonio de la escritura publica número 2010,
                    de fecha 24 de agosto de 2009, otorgada ante la fe de la Licenciada Adriana Lydia Villarreal Galindo, notario público número 46, en H. Matamoros, Tam.,
                    e inscrita en el Registro Público de Comercio de H. Matamoros bajo el número 1032*3 de fecha 7 de septiembre de 2009, y que su representante legal, en
                    este acto interviene con las facultades que se le confieren en el testimonio de la escritura pública número 2010, de fecha 24 de agosto de 2009, otorgada
                    ante la fe de la Licenciada Adriana Lydia Villarreal Galindo, notario público número 46 del 7 de septiembre de 2009, misma que se encuentra inscrita en el
                    Registro Público de Comercio, bajo el número 1032*3, y que dichas facultades no le han sido revocadas, modificadas o limitadas a la fecha de firma de este Contrato.</div>
                <div>
                    B) Que el domicilio, teléfono, Registro Federal de Contribuyentes y correo electrónico de “EL PROVEEDOR” se encuentran en la Carátula del
                    presente contrato.
                </div>
                <div>
                    C) Que cuenta con la capacidad, infraestructura, servicios, recursos necesarios y personal debidamente capacitado, para dar cabal
                    cumplimiento a las obligaciones derivadas del presente contrato.
                </div>
                <div>D) Que antes de la firma del presente Contrato ha explicado a “EL CONSUMIDOR” el contenido y alcance legal del mismo.</div>
                <div>E) Para la atención de dudas, aclaraciones, quejas o para proporcionar servicios de orientación, señala el domicilio ubicado en {{$sucursal->calle_num, $sucursal->colonia }}
                    CP {{$sucursal->codigo_postal}},{{$sucursal->municipio}}, {{$sucursal->estado}}, Tel: {{$sucursal->telefono_1}}, correo electronico: {{$sucursal->email}},
                    con un horario de atención de {{ $sucursal->horario_atencion }}.</div>
                <div>
                    F) Que la Prenda objeto de este Contrato se encuentra asegurada.
                </div>
                <b>II.- DECLARA “EL CONSUMIDOR”:</b>
                <div>A) Llamarse como ha quedado plasmado en el proemio de este contrato.</div>
                <div>B) Que manifesta su voluntad para obligarse en los términos y condiciones del presente contrato, y que cuenta con la capacidad legal para la
                    celebración del mismo.</div>
                <div>C) Que su domicilio, teléfono y correo electrónico se encuentran señalados en la Carátula del presente contrato.</div>
                <div>D) El consumidor manifesta bajo protesta en decir verdad que es el legal, legítmo e indiscutible propietario de la prenda de origen lícito que
                    entrega en garanta de este contrato, y de todo cuanto en derecho, uso y costumbre corresponden y que puede acreditar dicha calidad
                    jurídica ante terceros y/o cualquier autoridad que lo requiera.</div>
                <b>III. “LAS PARTES” declaran que:</b>
                <div>A) El contenido del presente Contrato constituye la totalidad de los acuerdos a que han llegado, por lo que “EL PROVEEDOR” se obliga a
                    entregarle un tanto a “EL CONSUMIDOR” a la firma del mismo.</div>
                <div>B) Reconocen que la información contenida en el presente contrato podrá ser compartida cuando sea solicitada por autoridad competente.
                    Expuesto lo anterior, “LAS PARTES” se sujetan al contenido de las siguientes:</div>
                <div class="text-center fw-bold">C L Á U S U L A S</div>
                <div>1.- CONSENTIMIENTO. - LAS PARTES manifestan su voluntad para celebrar el presente Contrato cuya naturaleza jurídica es mutuo con interés
                    y garanta prendaria (préstamo).</div>
                <div>2.- OBJETO. - “EL PROVEEDOR” entrega a “EL CONSUMIDOR” la cantidad de dinero en efectivo, equivalente al porcentaje de la valuación que
                    se ha practicado a la Prenda, en calidad de mutuo con interés y garantía prendaria y “EL CONSUMIDOR”, se obliga, al término del contrato, a
                    pagar a “EL PROVEEDOR”, la totalidad del dinero en efectivo, más los intereses, y las comisiones efectivamente devengadas que se estipulan
                    en la Carátula del presente Contrato.</div>
                <div>3.- ENTREGA DE LA PRENDA.- Con objeto de garantizar el cumplimiento de todas y cada una de las obligaciones derivadas de este Contrato,
                    “EL CONSUMIDOR” entregará a “EL PROVEEDOR” a título de Prenda, el o los bienes muebles que se describen en la Carátula del presente
                    Contrato, en el entendido de que esta entrega de ninguna manera convierte a “EL PROVEEDOR” en propietario de la Prenda, ni implica el
                    reconocimiento por parte de este, de que tal bien sea propiedad de “EL CONSUMIDOR” ni compromete, ni limita los derechos que terceras
                    personas pudieran tener sobre el mismo.</div>
                <div>4.- VALOR DE LA PRENDA. - El valor de la Prenda es el que se plasma en la Carátula del presente Contrato, por lo que LAS PARTES reconocen
                    que el mismo es resultado de un avalúo practicado por “EL PROVEEDOR” , con la presencia, autorización y conformidad de “EL CONSUMIDOR”.</div>
                <div>5.- DERECHOS Y OBLIGACIONES DE LAS PARTES. - “EL CONSUMIDOR” y “EL PROVEEDOR” aceptan y se obligan expresamente durante la
                    vigencia del Contrato a lo siguiente:</div>
                <b>“EL CONSUMIDOR”</b>
                <div><b>Obligaciones:</b></div>
                <div>a) Cumplir con todas las obligaciones derivadas del presente Contrato.</div>
                <div>b) Notificar a “EL PROVEEDOR”, dentro de un plazo que no exceda de 10 días naturales, siguientes a partir de aquel en que haya tenido
                    conocimiento de la existencia de cualquier acción, demanda, litigio o procedimiento en su contra, que comprometan a la Prenda.</div>
                <div>c) No enajenar, gravar, o comprometer los bienes entregados en garanta prendaría , mientras esté vigente el presente Contrato.</div>
                <div>d) “EL CONSUMIDOR” no podrá en ningún momento y por ningún motivo, ceder, dar en prenda, o traspasar, a título gratuito, oneroso, total o
                    parcialmente los derechos y obligaciones que le deriven de este contrato, ni el derecho a la propiedad o a la posesión de los bienes otorgados
                    en garanta prendaria, sin el consentimiento expreso y por escrito de “EL PROVEEDOR”.</div>
                <b>Derechos:</b>
                <div>a) Que "EL PROVEEDOR" haga la entrega del dinero en efectivo o en su caso , conforme a lo estipulado en el presente Contrato.</div>
                <div>b) Que se le devuelva la Prenda una vez que “EL CONSUMIDOR” haya cumplido con lo establecido en el presente Contrato.</div>
                <div>c) Recibir un ejemplar del presente Contrato firmado por LAS PARTES.</div>
                <div>d) Que “EL PROVEEDOR” le explique el contenido y alcance legal del presente Contrato.</div>
                <b>“EL PROVEEDOR”</b>
                <div><b>Obligaciones:</b></div>
                <div>a) “EL PROVEEDOR” se obliga a no usar la Prenda otorgada como garantía del mutuo , por lo que únicamente tendrá la guarda y custodia de
                    ésta.</div>
                <div>b) Cumplir con todas las obligaciones derivadas del presente Contrato.</div>
                <div>c) Conservar la Prenda como si fuera propia, y responder de los deterioros y perjuicios que sufra por su culpa o negligencia; en ningún caso
                    será responsable de los daños y deterioros que pudiere sufrir por el simple transcurso del tiempo.</div>
                <div>d) A restituir la Prenda dada en garantía, luego que esté pagada íntegramente la cantidad correspondiente a lo establecido en el presente
                    Contrato.</div>
                <div>e) Informar a “EL CONSUMIDOR” el Costo Anual Total (CAT), el Costo Mensual Total (CMT) y el Costo Diario Total (CDT) al momento de la
                    celebración del presente Contrato.</div>
                <div>f) Explicar a “EL CONSUMIDOR” el contenido y alcance legal del presente Contrato.</div>
                <b>Derechos:</b>
                <div>a) Que “EL CONSUMIDOR” le haga la entrega de la Prenda conforme a lo estipulado en el presente Contrato.</div>
                <div>b) Recibir los pagos correspondientes conforme a lo establecido en el presente Contrato.</div>
                <div>c) Poner en venta la Prenda en caso de incumplimiento o a solicitud de “EL CONSUMIDOR” , conforme a lo señalado en el presente Contrato.</div>
                <div>6.- DEFENSA DE LA PRENDA. - Si “EL PROVEEDOR” fuere perturbado en la posesión de la Prenda , por causas imputables a “EL CONSUMIDOR”,
                    avisará por escrito a este último, en un plazo que no exceda de tres días naturales para que lleve a cabo las acciones legales pertinentes; si
                    éste no cumpliere con esta obligación será responsable de todos los daños y perjuicios causados debidamente comprobados.</div>
                <div>7.- REPOSICIÓN DE LA PRENDA. - Si en el cumplimiento de mandato legítmo de autoridad competente , “EL PROVEEDOR” fuere desposeído de
                    la Prenda, “EL CONSUMIDOR” le entregará a su entera satisfacción otra prenda equivalente en peso , calidad, contenido, modelo, marca y
                    valor, dentro de los 10 días naturales siguientes a la notifcación que por escrito haga “EL PROVEEDOR” . En caso de omisión por parte de “EL
                    CONSUMIDOR” a la mencionada notifcación, desde este momento LAS PARTES acuerdan que sus derechos quedan a salvo para que éste
                    último los haga valer en la forma y vía que considere convenientes.</div>
                <div>8.- PROCEDIMIENTO PARA LA RESTITUCIÓN DE LA PRENDA PARA EL CASO DE PÉRDIDA O DETERIORO. - En caso de pérdida, robo, extravío o
                    deterioro de la Prenda, “EL PROVEEDOR” deberá contar con una garantía suficiente que le permita resarcir el siniestro , debiendo seguir el
                    siguiente procedimiento:</div>
                <div>a) “EL PROVEEDOR” deberá notificar a “EL CONSUMIDOR”, en un plazo que no exceda de 3 días naturales siguientes de ocurrido el
                    siniestro, por vía telefónica, correo electrónico, correo certificado, listas y/o anuncios publicados en el establecimiento de “EL PROVEEDOR”.</div>
                <div>b) “EL CONSUMIDOR”, deberá presentarse en el establecimiento donde firmó el contrato, en días y horas de servicio indicados en la
                    Carátula, o en el domicilio fiscal del proveedor, con la siguiente documentación:</div>
                <div>1) Contrato de adhesión, e</div>
                <div>2) Identificación de “EL CONSUMIDOR”, COTITULAR Y/O BENEFICIARIO.</div>
                <div>c) “EL PROVEEDOR” recibirá la documentación anterior, y le dará a “EL CONSUMIDOR” un comprobante en el cual se hará la
                    descripción de los documentos presentados, así como de la Prenda motivo de la reclamación, misma que deberá coincidir con la establecida
                    en el Contrato, indicando el valor de la Prenda conforme al avalúo practicado. El comprobante deberá de contener número de reclamación,
                    raz
                    ón social del proveedor, R.F.C., domicilio, nombre y firma de quien recibe la reclamación.</div>
                <div>d) “EL PROVEEDOR” se obliga a restituir o pagar la Prenda, a elección de “EL CONSUMIDOR”, en el término de 10 días naturales
                    siguientes a la entrega de la documentación por parte de este último.</div>
                <div>e) “EL PROVEEDOR” pagará a “EL CONSUMIDOR” el valor de la Prenda conforme al avalúo realizado y que está estipulado en la Carátula
                    de este Contrato, del cual se disminuirá la cantidad entregada por concepto de mutuo , los intereses y el almacenaje que se hayan devengado
                    hasta la fecha de ocurrido el siniestro y conforme a los porcentajes que se indica en la Carátula . “EL PROVEEDOR” podrá realizar el pago en
                    efectivo o mediante la entrega de un bien equivalente en modelo, marca, calidad, contenido, peso y valor a elección de “EL CONSUMIDOR”;
                    en ambos casos “EL PROVEEDOR” deberá pagar un 20% sobre el valor del avalúo, como pena convencional, siempre y cuando el siniestro haya
                    ocurrido por negligencia de éste.</div>
                Tratándose de metales preciosos, el valor de reposición del bien no podrá ser inferior al valor real que tenga el metal en el mercado al
                momento de la reposición.
                <div>9.- COSTO ANUAL TOTAL (CAT). - Es el costo de financiamiento que para fines informativos y de comparación, incorpora la totalidad de los
                    costos y gastos del préstamo. El referido Costo Anual Total se calculará utlizando la metodología establecida por el Banco de México, vigente
                    en la fecha del cálculo respectivo.</div>
            </td>
            <td style="width: 50%; vertical-align: top; padding-top: 0;">
                Para el cálculo de Costo Mensual Total (CMT) y del Costo Diario Total (CDT) se utlizará la misma metodología que se aplica para el cálculo
                del CAT establecida por el Banco de México, ajustando los valores de intervalo de tiempo que correspondan para el tipo de préstamo que se
                trate, vigente en la fecha del cálculo respectivo.
                <div>10.- INTERESES. - Metodología cálculo de interés ordinario. - El préstamo causará una tasa de interés fija del porcentaje anual mencionado
                    en la Carátula, sobre saldos insolutos más el Impuesto al Valor Agregado (IVA) cuando corresponda; el cálculo de intereses se realizará
                    multiplicando el saldo insoluto del préstamo, por la tasa de interés dividido entre 360 días por año, multiplicando por el número de días
                    transcurridos. La tasa de interés, así como su metodología de cálculo no podrán modificarse durante la vigencia del presente Contrato.</div>
                <div>11.- COMISIONES. - “EL CONSUMIDOR” se obliga, en su caso, a pagar a “EL PROVEEDOR”:</div>
                <div>a) Comisión por Almacenaje: El método de cálculo de comisión por almacenaje se realizará multiplicando el saldo insoluto del
                    préstamo otorgado, por la tasa de almacenaje diaria que aparece en la Carátula de este Contrato, más el Impuesto al Valor Agregado, por el
                    número de días efectivamente transcurridos.</div>
                <div>b) Comisión por avalúo: “EL PROVEEDOR” cobrará a “EL CONSUMIDOR” por concepto de avalúo de la Prenda el importe señalado en
                    la Carátula del presente Contrato.</div>
                <div>c) Comisión por Comercialización: Si “EL CONSUMIDOR” no cumpliese con el pago oportuno de la obligación principal, intereses y
                    comisiones estipuladas en el presente Contrato, “EL PROVEEDOR” procederá a comercializar el bien otorgado en garantía prendaria descrito
                    en este Contrato, con lo que “EL CONSUMIDOR” queda obligado a pagar a “EL PROVEEDOR” una comisión por el porcentaje detallado en la
                    Carátula, sobre el monto del préstamo.</div>
                <div>d) Comisión por Reposición del Contrato: “EL PROVEEDOR” cobrará a “EL CONSUMIDOR” por Reposición de Contrato, el monto que
                    se menciona en la Carátula. La solicitud de reposición deberá hacerse por escrito y presentada identificación.</div>
                <div>e) Desempeño Extemporáneo: “EL PROVEEDOR” cobrará a “EL CONSUMIDOR” por concepto de desempeño extemporáneo lo
                    señalado en la Carátula del presente Contrato.</div>
                <div>f) Gastos de administración: “EL PROVEEDOR” cobrará en su caso a “EL CONSUMIDOR” por Gastos de administración, el monto
                    establecido en la Carátula del presente Contrato.</div>
                <div>“EL PROVEEDOR” no podrá modificar las comisiones, ni la metodología de cálculo estipuladas en este Contrato durante la vigencia del
                    mismo.</div>
                <div>12.- MONTO DEL PRÉSTAMO. - El monto del préstamo es equivalente al porcentaje del valor del avalúo que se menciona en la Carátula y “EL
                    CONSUMIDOR” se obliga a restituir dicha cantidad, más los intereses, almacenaje y comisiones en su caso, en moneda nacional de curso
                    legal, sin menoscabo de poderlo hacer en moneda extranjera al tpo de cambio publicado por el Banco de México en el Diario Oficial de la
                    Federación al día en que el pago se efectúe. El monto del préstamo se realizará en efectivo, o en su caso, de conformidad a lo establecido
                    en la Carátula del presente Contrato.</div>
                <div>13.- CAUSAS DE TERMINACIÓN DEL CONTRATO. - Serán causas de terminación del Contrato:</div>
                <div>a) Pago del Préstamo. - En el plazo establecido en la Carátula del presente Contrato “EL CONSUMIDOR” deberá reintegrar el importe del
                    mutuo, conjuntamente con los intereses, almacenaje y las comisiones pactadas en el Contrato. El pago será hecho en el establecimiento en
                    que se suscribe el mismo, en moneda nacional de curso legal, sin menoscabo de poderlo hacer en moneda extranjera al tipo de cambio
                    publicado por el Banco de México en el Diario Ofcial de la Federación al día en que el pago se efectúe; cuando el término de la opción de
                    pago corresponda a un día inhábil, el pago deberá hacerse el siguiente día hábil. Realizado el pago “EL CONSUMIDOR” recibirá la Prenda en
                    el mismo lugar en que la entregó, otorgándose ambas partes el finiquito más amplio que en derecho proceda.</div>
                <div>b) Pago Anticipado. - “EL CONSUMIDOR” tendrá el derecho de cubrir el saldo total del mutuo, almacenaje y demás comisiones pactadas y
                    efectivamente devengadas, antes del vencimiento del plazo establecido en la Carátula del presente Contrato, conforme a las opciones de
                    pago descritas en este, en cuyo caso “EL CONSUMIDOR” deberá presentarse en el establecimiento. Efectuado el pago se procederá a la
                    devolución de la Prenda en el acto.
                    “EL CONSUMIDOR” podrá realizar pagos anticipados, siempre y cuando se encuentre al corriente de sus pagos, por lo que los pagos
                    anticipados se aplicarán al saldo insoluto principal. “EL CONSUMIDOR” deberá avisar por escrito a “EL PROVEEDOR” su deseo de realizar los
                    pagos anticipados, por lo que “EL PROVEEDOR” deberá aplicar dicho pago anticipado al saldo insoluto.</div>
                <div>14.- COMERCIALIZACIÓN DE LA PRENDA.- Para el caso de que “EL CONSUMIDOR” no cumpliera oportunamente con la obligación de
                    restituir el mutuo, los intereses, almacenaje y demás comisiones pactadas en el Contrato, en este acto se otorga expresamente a favor de
                    “EL PROVEEDOR” un mandato aplicado a actos concretos de comercio, en los términos del artículo 273 del Código de Comercio, para que a
                    título de comisionista en su nombre y representación y sin necesidad de agotar trámite alguno, efectúe la venta de la Prenda, tomando
                    como referencia el valor del avalúo estipulado en la cláusula cuarta del presente Contrato, sirviendo como notificación de la fecha de inicio
                    de su comercialización la indicada en la Carátula de este Contrato. Para los efectos de la exención a que se refere el artculo 9 fracción IV ,
                    de la Ley de Impuesto al Valor Agregado, “EL CONSUMIDOR” reconoce explícitamente ser el enajenante de la Prenda, que ésta es usada y
                    no tener la condición jurídica de empresa.</div>
                <div>15.- APLICACIÓN DEL PRODUCTO DE LA VENTA Y REMANENTE. - “EL CONSUMIDOR” autoriza a “EL PROVEEDOR” a aplicar el producto de la
                    venta de la Prenda, al pago de la obligación principal, a los intereses, almacenaje, y comisión por comercialización. Si al realizar la venta o el
                    remate de la Prenda hubiera algún remanente, el mismo será puesto a disposición de “EL CONSUMIDOR”, a partir del tercer día siguiente a
                    la comercialización de la Prenda, para lo cual “EL PROVEEDOR” dentro de dicho plazo, notificará por teléfono, correo electrónico, correo
                    certificado y/o listas colocadas en el establecimiento respecto de la venta de la Prenda. El remanente no cobrado en un lapso de doce
                    meses calendario, contados a partir de la fecha de comercialización de la Prenda quedará a favor de “EL PROVEEDOR”.</div>
                <div>16.- DESEMPEÑO EXTEMPORÁNEO. - En el caso de que la Prenda no haya sido comercializada después de la “FECHA LÍMITE DE REFRENDO
                    O DESEMPEÑO”, tal como se señala en la Carátula de este Contrato, “EL CONSUMIDOR” podrá recuperar la Prenda previo acuerdo con “EL
                    PROVEEDOR” y pago del mutuo, de los intereses, almacenaje y las comisiones pactadas en el presente Contrato. Para tal efecto, “EL
                    CONSUMIDOR” deberá solicitar por escrito a “EL PROVEEDOR” autorización para hacer el pago adeudado en un término no mayor a 3 (tres)
                    días hábiles de la fecha límite de refrendo o desempeño. En caso de que “EL PROVEEDOR” no dé contestación a lo solicitado, se entenderá
                    como negada dicha autorización.</div>
                <div>17.- REFRENDO. - “EL CONSUMIDOR” podrá refrendar el Contrato, antes o en la fecha de su terminación, con el consentimiento de “EL
                    PROVEEDOR”, siempre y cuando “EL CONSUMIDOR” cubra el pago de los intereses, almacenaje y comisiones efectivamente devengadas al
                    momento del refrendo. Al efectuarse el refrendo se firmará un nuevo Contrato con intereses, almacenaje y comisiones aplicables al
                    momento del refrendo.</div>
                <div>18.- PENA CONVENCIONAL. - En caso de incumplimiento de cualquiera de las obligaciones a cargo de “EL PROVEEDOR”, éste pagará a “EL
                    CONSUMIDOR” una pena convencional del 20 % (veinte por ciento) sobre el monto del avalúo, por lo que “EL CONSUMIDOR” deberá
                    solicitar el monto de la Pena por escrito en el domicilio de “EL PROVEEDOR” , indicando dicho incumplimiento.
                    Por lo anterior, “EL PROVEEDOR” deberá hacer el pago por la Pena convencional en un lapso no mayor a 10 (diez) días naturales contados a
                    partir de la recepción de la solicitud efectuada por “EL CONSUMIDOR”.</div>
                <div>19.- NULIDAD. - De haber alguna causal de nulidad determinada por autoridad competente, la misma afectará solamente a la cláusula en la
                    que específcamente se hubiere incurrido en el vicio señalado.</div>
                <div>20.- LEGITIMIDAD. - Para el ejercicio de los derechos o el cumplimiento de los deberes a su cargo, “EL CONSUMIDOR” o en su defecto su
                    Cotitular, benefciario o representante legal, invariablemente deberán presentar a “EL PROVEEDOR” este contrato, así como una
                    identfcación expedida por autoridad competente, en el establecimiento donde suscribió el contrato, en los días y horas de servicio
                    indicados en la Carátula de este contrato. En caso de extravío del Contrato “EL CONSUMIDOR” podrá tramitar su reposición solicitándolo
                    por escrito y cubriendo el importe señalado en la Carátula del mismo.</div>
                <div>21.- CONFIDENCIALIDAD.- Ambas partes convienen en que el acuerdo de voluntades que suscriben tene el carácter de confidencial, por lo
                    que “EL PROVEEDOR” se obliga a mantener los datos relativos a “EL CONSUMIDOR” con tal carácter y únicamente podrá ser revelada la
                    información contenida en el mismo por mandamiento de autoridad competente; de igual forma se obliga a no ceder o transmitir a terceros
                    con fines mercadotécnicos o publicitarios los datos e información proporcionada por “EL CONSUMIDOR” con motvo del Contrato, ni enviar
                    publicidad sobre los bienes y servicios, salvo que conste la autorización expresa de “EL CONSUMIDOR” en la Carátula y al final del presente
                    Contrato.</div>
                <div>22.- NOTIFICACIONES. - LAS PARTES acuerdan que cualquier notifcación o aviso con motivo del Contrato, deberá realizarse en los domicilios
                    que se hayan establecido por las mismas, los cuales se indican en la Carátula; de igual manera, LAS PARTES podrán efectuar avisos o
                    comunicados mediante, teléfono, correo electrónico, correo certificado y/o listas colocadas en el establecimiento.</div>
                <div>23.- DÍAS INHÁBILES. - Todas las obligaciones contenidas en este Contrato, cuyo vencimiento tenga lugar en un día inhábil, deberá
                    considerarse que el vencimiento de las mismas, será el día hábil siguiente.</div>
                <div>24.- DERECHO APLICABLE. -</div>
                <div>EN CASO DE PERSONA MORAL: Este Contrato se rige por lo dispuesto en la Ley Federal de Protección al Consumidor y su Reglamento; la
                    Norma Oficial Mexicana NOM-179-SCFI-2016 Servicios de mutuo con interés y garantía prendaria; Disposiciones de carácter general a que
                    se refere la Ley para la Transparencia y Ordenamiento de los Servicios Financieros en materia de Contratos de Adhesión, Publicidad,
                    Estados de Cuenta y Comprobantes de Operación emitidos por las Entidades Comerciales y demás ordenamientos aplicables.
                    EN CASO DE PERSONA FÍSICA: Este Contrato se rige por lo dispuesto en la Ley Federal de Protección al Consumidor y su Reglamento, la
                    Norma Oficial Mexicana NOM-179-SCFI-2016 Servicios de mutuo con interés y garantía prendaría y demás ordenamientos aplicables.</div>
                <div>25.- ACLARACIONES, QUEJAS O RECLAMACIONES. - En caso de aclaraciones, quejas, reclamaciones o reposición de contrato por pérdida o
                    destrucción, “EL CONSUMIDOR” deberá comunicarse al centro de atención de “EL PROVEEDOR”, al número telefónico o presentarse en el
                    domicilio que se establece en la Carátula. “EL PROVEEDOR” deberá proporcionar un número de reporte a “EL CONSUMIDOR”, con el que se
                    identificará la aclaración, queja o reclamación y se dará seguimiento al trámite, el cual será atendido en un tiempo no mayor a 10 días
                    naturales.</div>
                <div>26.- CONTRATACIÓN POR MEDIOS ELECTRÓNICOS.- Las partes acuerdan que en lugar de una firma original autógrafa, este contrato, así
                    como cualquier consentmiento, aprobación u otros documentos relacionados con el mismo, podrán ser firmados por medio del uso de
                    firmas electrónicas, digitales, numéricas, alfanuméricas, huellas de voz, biométricas o de cualquier otra forma y que dichos medios
                    alternatvos de firma y los registros en donde sean aplicadas dichas firmas, serán consideradas para todos los efectos, incluyendo pero no
                    limitado a la legislación civil, mercantil, protección al consumidor y a la NOM-151-SCFI-2016, con la misma fuerza y consecuencias que la
                    firma autógrafa original fsica de la parte firmante. Si el contrato o cualquier otro documento relacionado con el mismo es firmado por
                    medios electrónicos o digitales, las Partes acuerdan que los formatos del contrato y los demás documentos firmados de tal modo serán
                    conservados y estarán a disposición del consumidor, por lo que convienen que cada una y toda la información enviada por el Proveedor a la
                    dirección de correo electrónico proporcionada por el Consumidor al momento de celebrar el presente Contrato será considerada como
                    entregada en el momento en que la misma es enviada , siempre y cuando exista confirmación de recepción.</div>
                <div> 27.- JURISDICCIÓN. - Para todo lo relativo a la interpretación, aplicación y cumplimiento del contrato, LAS PARTES acuerdan someterse en la
                    vía administrativa a la Procuraduría Federal del Consumidor, y en caso de subsistir diferencias, a la jurisdicción de los tribunales
                    competentes del lugar donde se celebra este Contrato.
                    Leído que fue y una vez hecha la explicación de su alcance legal y contenido, este Contrato fue suscrito por duplicado en el lugar y en la
                    fecha que se indica en la Carátula del mismo, entregándosele una copia a “EL CONSUMIDOR”.</div>
                <table class="table my-2" style="text-align: center">
                    <tr>
                        <td align="center" class="text-center">_______________________________</td>
                        <td align="center" class="text-center">_______________________________</td>
                    </tr>
                    <tr>
                        <td align="center" class="text-center">"EL CONSUMIDOR”</td>
                        <td align="center" class="text-center">"EL PROVEEDOR”</td>
                    </tr>
                </table>
                Este contrato fue aprobado y registrado por la Procuraduría Federal del Consumidor bajo el número {{$sucursal->adhesion_num}} de fecha {{$fechaAdhesion}}.
                Cualquier variación del presente contrato en perjuicio de “EL CONSUMIDOR”, frente al contrato de adhesión registrado, se tendrá por no puesta.
                <div class="mb-2">______________________________________________________________________________________________</div>
                <div>Autorización para el uso de información proporcionada con fines mercadotécnicos o publicitarios</div>
                <div>“EL CONSUMIDOR” SÍ ( ) No ( ) acepta que “EL PROVEEDOR” ceda o transmita a terceros, con fines mercadotécnicos o publicitarios, la
                    información proporcionada con motivo del presente Contrato y SÍ acepta ( ) NO acepta ( ) que “EL PROVEEDOR” le envíe publicidad sobre
                    bienes y servicios.</div>
                <div class="text-center mt-2" style="text-align: center !important;">
                    <br>
                    <br>
                    <div>____________________________________________</div>
                    <div>Firma de autorización de “EL CONSUMIDOR”</div>
                </div>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
