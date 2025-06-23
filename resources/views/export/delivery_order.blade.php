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
            margin: 0;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            /* --- Style updates below --- */
            border-bottom: 1px solid #000;
            /* This adds the line */
            padding-bottom: 10px;
            /* This adds some space before the line */
        }

        .company-details {
            display: flex;
            align-items: flex-start;
        }

        .logo {
            width: 70px;
            margin-right: 10px;
        }

        .company-text {
            font-size: 11px;
            line-height: 1.4;
        }

        .company-name {
            font-weight: bold;
            color: #b91c1c;
            font-size: 13px;
        }

        .do-title {
            font-size: 20px;
            font-weight: bold;
            color: #103f66;
            margin-top: 0;
        }

        .section {
            margin: 20px 0 10px;
        }

        .section strong {
            font-size: 13px;
        }

        .meta-box {
            border: 1px solid black;
            width: 100%;
            margin-top: 5px;
        }

        .meta-box td {
            padding: 8px 12px;
            font-size: 12px;
            border: 1px solid black;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table.items th {
            background-color: #103f66;
            color: #fff;
            padding: 8px;
            font-size: 12px;
            border: 1px solid black;
            text-align: left;
        }

        table.items td {
            border: 1px solid black;
            padding: 8px;
            font-size: 12px;
        }

        .total-row td {
            font-weight: bold;
        }

        .signature-section {
            margin-top: 60px;
            display: flex;
            justify-content: space-between;
        }

        .signature-box {
            width: 40%;
            text-align: center;
        }

        .signature-label {
            font-weight: bold;
            margin-bottom: 40px;
        }

        .signature-line {
            border-top: 1px solid #000;
            margin: 40px auto 0;
            width: 80%;
        }
    </style>
</head>

<body>

    <div class="header">
        <table style="width: 100%; margin-bottom: 10px;">
            <tr>
                <td style="width: 70%;">
                    <table style="width: 100%;">
                        <tr>
                            <td style="width: 70px;">
                                <img src="{{ asset('/storage/' . setting('company_logo')) }}" height="60">
                            </td>
                            <td style="font-size: 11px; line-height: 1.4;">
                                <div style="font-weight: bold; color: #b91c1c; font-size: 13px;">
                                    {{ settingWithDefault('company_name', 'Company Name') }}
                                </div>
                                {!! nl2br(e(settingWithDefault('address'))) !!}<br>
                                Tel. No.: {{ settingWithDefault('company_phone', '0000000000') }}<br>
                                Website: {{ settingWithDefault('website', 'www.example.com') }}
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="text-align: right; vertical-align: top;">
                    <div style="font-size: 20px; font-weight: bold; color: #103f66;">
                        Delivery Order
                    </div>
                </td>
            </tr>
        </table>

    </div>

    <div class="section">
        <strong>To:</strong><br>
        <div><strong>{{ $deliveryInfo['customer_name'] }}</strong></div>
        <div style="max-width: 300px;">
            {{ $deliveryInfo['branch_address'] }}
        </div>
    </div>

    <table class="meta-box">
        <tr>
            <td><strong>Date:</strong> {{ $deliveryInfo['do_date'] }}</td>
            <td><strong>Order #</strong> {{ $deliveryInfo['id'] }}</td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 5%">#</th>
                <th style="width: 55%">Product</th>
                <th style="width: 20%">UNIT</th>
                <th style="width: 20%">QTY</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($deliveryInfo['items'] as $item)
                <tr>
                    <td>{{ $item['index'] }}</td>
                    <td>{{ $item['name'] }}</td>
                    <td>{{ $item['unit'] ?? '-' }}</td>
                    <td>{{ number_format($item['quantity'], 2) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3" style="text-align: right;">Total QTY</td>
                <td>{{ number_format($deliveryInfo['total_qty'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table style="width: 100%; margin-top: 60px;">
        <tr>
            <td style="width: 50%; text-align: center;">
                <strong>Receiver's Signature:</strong>
                <hr style="border: none; border-top: 1px solid #000; width: 80%; margin-top: 40px;">
            </td>
            <td style="width: 50%; text-align: center;">
                <strong>Company Chop:</strong>
                <hr style="border: none; border-top: 1px solid #000; width: 80%; margin-top: 40px;">
            </td>
        </tr>
    </table>


</body>

</html>
