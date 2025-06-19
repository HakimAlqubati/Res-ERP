<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Delivery Order</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #000;
        }

        h2 {
            color: #103f66;
            margin-bottom: 0;
        }

        .header {
            text-align: left;
            margin-bottom: 10px;
        }

        .company-info {
            font-weight: bold;
            color: #b91c1c;
        }

        .contact-info {
            font-size: 10px;
            line-height: 1.4;
        }

        .delivery-label {
            color: #103f66;
            font-size: 20px;
            font-weight: bold;
            text-align: right;
        }

        .section {
            margin: 15px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        table th,
        table td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
        }

        table th {
            background-color: #103f66;
            color: white;
        }

        .total-row td {
            font-weight: bold;
            text-align: right;
        }

        .signature-section {
            margin-top: 50px;
        }

        .signature-box {
            width: 45%;
            display: inline-block;
            vertical-align: top;
        }

        .signature-box p {
            margin-top: 50px;
            border-top: 1px solid #000;
            width: 80%;
        }
    </style>
</head>

<body>

    <div class="header">
        <img src="{{ public_path('images/logo.png') }}" height="50" style="float: left;">
        <div class="company-info">Al-Sultan Restaurant</div>
        <div class="contact-info">
            35, Jalan Penguasa U1/53A, Kawasan Perindustrian Temasya,<br>
            Hicom Glenmarie Industrial Park, 40150 Shah Alam, Selangor<br>
            Tel: +603-5567 0110 &nbsp;&nbsp; Mobile: +6011-6514 0110<br>
            Website: www.alsultanglenmarie.com
        </div>
        <div class="delivery-label">Delivery Order</div>
        <div style="clear: both;"></div>
    </div>

    <div class="section">
        <strong>To:</strong><br>
        <strong>{{ $deliveryInfo['customer_name'] }}</strong><br>
        {{ $deliveryInfo['address'] }}
    </div>

    <div class="section">
        <table>
            <tr>
                <td><strong>Date:</strong> {{ $deliveryInfo['do_date'] }}</td>
                <td><strong>DO No.:</strong> {{ $deliveryInfo['do_number'] }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <table>
            <thead>
                <tr>
                    <th style="width: 5%">#</th>
                    <th style="width: 70%">DESCRIPTION</th>
                    <th style="width: 25%">QTY</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($deliveryInfo['items'] as $item)
                    <tr>
                        <td>{{ $item['index'] }}</td>
                        <td>{{ $item['name'] }}</td>
                        <td>{{ $item['quantity'] }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="2">Total QTY</td>
                    <td>{{ $deliveryInfo['total_qty'] }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="signature-section">
        <div class="signature-box">
            <strong>Receiver's Signature:</strong>
            <p></p>
        </div>
        <div class="signature-box" style="float: right; text-align: right;">
            <strong>Company Chop:</strong>
            <p></p>
        </div>
    </div>

</body>

</html>
