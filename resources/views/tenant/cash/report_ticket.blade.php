@php
    // Inicialización de variables básicas
    $establishment = $cash->user->establishment ?? null;
    $cash_income = 0;
    $cash_taxes = 0;
    $document_count = 0;
    $first_document = '';
    $last_document = '';
    
    // Obtener documentos de caja de forma segura
    $cash_documents = collect();
    try {
        $cash_documents = $cash->cash_documents ?? collect();
    } catch (Exception $e) {
        $cash_documents = collect();
    }
    
    // Calcular egresos
    $cashEgress = 0;
    try {
        $cashEgress = $cash_documents->sum(function ($cashDocument) {
            return $cashDocument->expense_payment ? ($cashDocument->expense_payment->payment ?? 0) : 0;
        });
    } catch (Exception $e) {
        $cashEgress = 0;
    }

    // Inicializar métodos de pago de forma segura
    if (isset($methods_payment) && $methods_payment) {
        foreach ($methods_payment as $method) {
            $method->sum = 0;
            $method->transaction_count = 0;
        }
    }

    // Filtrar documentos POS válidos
    $valid_documents = collect();
    try {
        $valid_documents = $cash_documents->filter(function ($item) {
            return isset($item->document_pos_id) && $item->document_pos_id !== null;
        });
    } catch (Exception $e) {
        $valid_documents = collect();
    }

    // Procesar documentos válidos
    if ($valid_documents->count() > 0) {
        try {
            // Obtener primer y último documento
            $first_valid = $valid_documents->first();
            $last_valid = $valid_documents->last();
            
            if ($first_valid && isset($first_valid->document_pos)) {
                $first_document = ($first_valid->document_pos->series ?? '') . '-' . ($first_valid->document_pos->number ?? '');
            }
            
            if ($last_valid && isset($last_valid->document_pos)) {
                $last_document = ($last_valid->document_pos->series ?? '') . '-' . ($last_valid->document_pos->number ?? '');
            }
            
            $document_count = $valid_documents->count();

            // Procesar cada documento para calcular ingresos
            foreach ($valid_documents as $cash_document) {
                if (isset($cash_document->document_pos) && $cash_document->document_pos) {
                    $document_pos = $cash_document->document_pos;
                    
                    // Calcular total del documento
                    $document_total = $document_pos->total ?? 0;
                    $cash_income += $document_total;
                    
                    // Procesar pagos si existen métodos de pago
                    if (isset($methods_payment) && $methods_payment && isset($document_pos->payments)) {
                        $payments = $document_pos->payments;
                        
                        if ($payments && count($payments) > 0) {
                            foreach ($payments as $payment) {
                                $payment_method_id = $payment->payment_method_type_id ?? null;
                                $payment_amount = $payment->payment ?? 0;
                                
                                if ($payment_method_id && $payment_amount > 0) {
                                    $method = $methods_payment->firstWhere('id', $payment_method_id);
                                    if ($method) {
                                        $method->sum += $payment_amount;
                                        $method->transaction_count++;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // En caso de error, mantener valores por defecto
        }
    }

    // Calcular saldo final
    $beginning_balance = $cash->beginning_balance ?? 0;
    $cash_final_balance = $beginning_balance + $cash_income - $cashEgress;

@endphp

<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="Content-Type" content="application/pdf; charset=utf-8" />
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>Reporte POS - {{ $cash->user->name }} - {{ $cash->date_opening }} {{ $cash->time_opening }}</title>
        <style>
            html {
                font-family: sans-serif;
                font-size: 12px;
            }

            table {
                width: 100%;
                border-spacing: 0;
                border: 1px solid black;
            }

            .celda {
                text-align: center;
                padding: 5px;
                border: 0.1px solid black;
            }

            th {
                padding: 5px;
                text-align: center;
                border-color: #0088cc;
                border: 0.1px solid black;
            }

            .title {
                font-weight: bold;
                padding: 5px;
                font-size: 20px !important;
                text-decoration: underline;
            }

            p>strong {
                margin-left: 5px;
                font-size: 12px;
            }

            thead {
                font-weight: bold;
                background: #0088cc;
                color: white;
                text-align: center;
            }

            tbody {
                text-align: right;
            }

            .text-center {
                text-align: center;
            }

            .td-custom {
                line-height: 0.1em;
            }

            .totales {
                font-weight: bold;
                background: #0088cc;
                color: white;
                text-align: right;
            }

            html {
                font-family: sans-serif;
                font-size: 8px;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                border: 1px solid black;
            }

            th,
            td {
                padding: 2px;
                border: 1px solid black;
                text-align: center;
                font-size: 8px;
            }

            th {
                background-color: #0088cc;
                color: white;
                font-weight: bold;
            }

            .title {
                font-weight: bold;
                text-align: center;
                font-size: 16px;
                text-decoration: underline;
            }

            p,
            p>strong {
                font-size: 8px;
            }

            .totales {
                font-weight: bold;
                background: #0088cc;
                color: white;
                text-align: right;
            }

            /* Estilos encabezado */
            html {
                font-family: sans-serif;
                font-size: 12px;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                border: 1px solid black;
            }

            th,
            .celda {
                padding: 5px;
                border: 1px solid black;
                text-align: center;
            }

            th {
                background-color: #0088cc;
                color: white;
                font-weight: bold;
            }

            .title {
                font-weight: bold;
                text-align: center;
                font-size: 20px;
                text-decoration: underline;
            }
        </style>
    </head>

    <body>
        <div>
            {{-- <p align="center" class="title"><strong>COMPROBANTE INFORME DIARIO</strong></p> --}}
            <div style="margin-top: -30px;" class="text-center">
                <p>
                    <strong>Empresa: </strong>{{ $company->name }} <br>
                    <strong>N° Documento: </strong>{{ $company->number }} <br>
                    <strong>Establecimiento: </strong>{{ $establishment->description }} <br>
                    <strong>Fecha reporte: </strong>{{ date('Y-m-d') }} <br>
                    <strong>Vendedor:</strong> {{ $cash->user->name }} <br>
                    <strong>Fecha y hora apertura:</strong> {{ $cash->date_opening }} {{ $cash->time_opening }} <br>
                    <strong>Estado de caja:</strong> {{ $cash->state ? 'Aperturada' : 'Cerrada' }}
                    @if (!$cash->state)
                        <br>
                        <strong>Fecha y hora cierre:</strong> {{ $cash->date_closed }} {{ $cash->time_closed }}
                    @endif
                </p>
            </div>
        </div>
        @php
            $is_complete = $only_head === 'resumido' ? false : true;
        @endphp

        <div>
            @php
                $tipoComprobante = 'Factura POS';
                $numeroInicial = null;
                $numeroFinal = null;

                foreach ($cash_documents as $cash_document) {
                    if ($cash_document->document_pos) {
                        $numeroActual = $cash_document->document_pos->number_full;
                        if (!$numeroInicial || $numeroActual < $numeroInicial) {
                            $numeroInicial = $numeroActual;
                        }
                        if (!$numeroFinal || $numeroActual > $numeroFinal) {
                            $numeroFinal = $numeroActual;
                        }
                    }
                }
            @endphp
        </div>

        <!-- Información de debug (temporal para diagnosticar) -->
        <div style="font-size: 10px; color: #666; margin-bottom: 10px; border: 1px solid #ccc; padding: 5px;">
            <strong>Info:</strong><br>
            Total documentos en caja: {{ $cash_documents ? $cash_documents->count() : 0 }}<br>
            Documentos POS válidos: {{ $valid_documents ? $valid_documents->count() : 0 }}<br>
            Saldo inicial: ${{ number_format($beginning_balance, 2) }}<br>
            Ingresos calculados: ${{ number_format($cash_income, 2) }}<br>
            Egresos calculados: ${{ number_format($cashEgress, 2) }}<br>
            Métodos de pago disponibles: {{ isset($methods_payment) ? $methods_payment->count() : 0 }}<br>
            Estado de caja: {{ $cash->state ? 'Abierta' : 'Cerrada' }}<br>
            @if($valid_documents && $valid_documents->count() > 0)
                Primer documento: {{ $first_document ?: 'N/A' }}<br>
                Último documento: {{ $last_document ?: 'N/A' }}<br>
            @else
                <span style="color: red;">⚠️ No hay documentos POS válidos</span>
            @endif
        </div>

        <!-- Tabla de resumen de saldos -->
        <table>
            <tr>
                <th>Saldo inicial</th>
                <th>Ingreso</th>
                <th>Egreso</th>
                <th>Saldo final</th>
            </tr>
            <tr>
                <td>${{ number_format($beginning_balance, 2, '.', ',') }}</td>
                <td>${{ number_format($cash_income, 2, '.', ',') }}</td>
                <td>${{ number_format($cashEgress, 2, '.', ',') }}</td>
                <td>${{ number_format($cash_final_balance, 2, '.', ',') }}</td>
            </tr>
        </table>

        <table>
            <tr>
                <th>Egreso</th>
            </tr>
            <tr>
                <td>${{ number_format($cashEgress, 2, '.', ',') }}</td>
            </tr>
        </table>

        @if ($valid_documents && $valid_documents->count() > 0)
            <div>
                <h3>Totales por medio de pago</h3>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Medio de Pago</th>
                            <th>Número de Transacciones</th>
                            <th>Valor Transacción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php 
                            $totalSum = 0; 
                            $totalTransactions = 0;
                            $hasPayments = false;
                        @endphp
                        @if(isset($methods_payment) && $methods_payment)
                            @foreach ($methods_payment as $item)
                                @if (($item->sum ?? 0) > 0 || ($item->transaction_count ?? 0) > 0)
                                    @php
                                        $totalSum += $item->sum ?? 0;
                                        $totalTransactions += $item->transaction_count ?? 0;
                                        $hasPayments = true;
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $item->name ?? 'N/A' }}</td>
                                        <td>{{ $item->transaction_count ?? 0 }}</td>
                                        <td>${{ number_format($item->sum ?? 0, 2, '.', ',') }}</td>
                                    </tr>
                                @endif
                            @endforeach
                        @endif
                        
                        @if (!$hasPayments)
                            <tr>
                                <td colspan="4" style="text-align: center; color: #666;">
                                    No se registraron transacciones con métodos de pago específicos
                                </td>
                            </tr>
                        @else
                            <tr style="background-color: #f0f0f0; font-weight: bold;">
                                <td colspan="2"><strong>TOTALES:</strong></td>
                                <td><strong>{{ $totalTransactions }}</strong></td>
                                <td><strong>${{ number_format($totalSum, 2, '.', ',') }}</strong></td>
                            </tr>
                        @endif
                    </tbody>
                </table>
                
                <!-- Información adicional del período -->
                @if ($first_document && $last_document)
                    <div style="margin-top: 10px; font-size: 10px;">
                        <strong>Rango de documentos:</strong> {{ $first_document }} - {{ $last_document }}<br>
                        <strong>T.Documentos proc.:</strong>{{ $document_count }}</div>
                @endif
            </div>
        @else
            <div>
                <h3>Totales por medio de pago</h3>
                <p style="text-align: center; color: #666; padding: 20px;">
                    No se encontraron documentos POS para este período de caja.<br>
                    <small>Verifique que haya ventas registradas en el sistema.</small>
                </p>
            </div>
        @endif
    </body>
</html>
