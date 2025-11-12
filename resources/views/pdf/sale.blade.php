<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>{{ $sale['header']['invoice_number'] }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #333;
        }

        h1 {
            font-size: 18px;
            margin-bottom: 10px;
        }

        .header,
        .totals,
        .items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .items th,
        .items td {
            border: 1px solid #ddd;
            padding: 6px;
        }

        .items th {
            background: #f5f5f5;
            text-align: left;
        }

        .totals td {
            padding: 4px;
        }

        .right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        .section-title {
            margin-top: 20px;
            font-weight: bold;
            border-bottom: 1px solid #aaa;
        }
    </style>
</head>

<body>

    <h1>Factura {{ $sale['header']['invoice_number'] }}</h1>

    <table class="header">
        <tr>
            <td><strong>Fecha emisión:</strong> {{ $sale['header']['issued_at'] }}</td>
            <td><strong>Cliente:</strong> {{ $sale['header']['customer']['name'] ?? '—' }}</td>
        </tr>
        <tr>
            <td><strong>Documento:</strong> {{ $sale['header']['customer']['document'] ?? '—' }}</td>
            <td><strong>Teléfono:</strong> {{ $sale['header']['customer']['phone'] ?? '—' }}</td>
        </tr>
        <tr>
            <td colspan="2"><strong>Dirección:</strong> {{ $sale['header']['customer']['address'] ?? '—' }}</td>
        </tr>
    </table>

    <div class="section-title">Detalle de productos</div>
    <table class="items">
        <thead>
            <tr>
                <th>Código</th>
                <th>Producto</th>
                <th class="right">Cantidad</th>
                <th class="right">Precio</th>
                <th class="right">Descuento</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale['items'] as $i)
            <tr>
                <td>{{ $i['sku'] }}</td>
                <td>{{ $i['name'] }}</td>
                <td class="right">{{ $i['quantity'] }}</td>
                <td class="right">${{ number_format($i['unit_price'], 0, ',', '.') }}</td>
                <td class="right">${{ number_format($i['discount'], 0, ',', '.') }}</td>
                <td class="right">${{ number_format($i['line_total'], 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">Totales</div>
    <table class="totals">
        <tr>
            <td>Subtotal:</td>
            <td class="right">${{ number_format($sale['header']['totals']['subtotal'], 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Descuento:</td>
            <td class="right">${{ number_format($sale['header']['totals']['discount'], 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>IVA (19%):</td>
            <td class="right">${{ number_format($sale['header']['totals']['tax'], 0, ',', '.') }}</td>
        </tr>
        <tr class="bold">
            <td>Total:</td>
            <td class="right">${{ number_format($sale['header']['totals']['total'], 0, ',', '.') }}</td>
        </tr>
    </table>

    <p style="margin-top: 40px; font-size: 10px; color: #777;">Documento generado automáticamente.</p>

</body>

</html>