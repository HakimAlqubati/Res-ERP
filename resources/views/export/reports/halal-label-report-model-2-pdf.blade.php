<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Halal Label Sticker</title>
    <style>
        body {
            font-family: 'Arial', sans-serif !important;
            font-size: 10px;
            color: black;
            background-color: #ffffff;
            direction: ltr !important;
            margin: 0;
            padding: 0;
        }

        .label-card {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
            page-break-after: always;
            height: 100%;
        }

        .label-card:last-child {
            page-break-after: auto;
        }

        .card-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
        }

        .card-td-left {
            width: 70%;
            vertical-align: top;
            border: none;
            padding: 0;
            padding-right: 8px;
        }

        .card-td-right {
            width: 30%;
            vertical-align: bottom;
            text-align: center;
            border: none;
            padding: 0;
        }

        .info-row {
            margin-bottom: 16px;
            line-height: 1.6;
        }

        .bold-label {
            font-weight: bold;
        }

        .product-name-val {
            font-weight: bold;
            font-size: 13px;
            text-transform: uppercase;
            display: block;
            margin-top: 2px;
        }

        .val-text {
            display: block;
            margin-top: 1px;
        }
    </style>
</head>

<body>

    @php
    $companyName = \App\Models\Setting::getSetting('company_name');
    $address = \App\Models\Setting::getSetting('address');
    $tel = \App\Models\Setting::getSetting('company_phone');
    $manufacturerInfo = $companyName . '. ' . $address;
    @endphp

    @foreach ($reportData as $index => $row)
    <div class="label-card">
        <table class="card-table">
            <tr>
                <td class="card-td-left">
                    <div class="info-row">
                        <span class="bold-label">Product Name/Nama Produk:</span>
                        <span class="product-name-val">{{ $row['product_name'] }}</span>
                    </div>

                    <div class="info-row">
                        <span class="bold-label">Manufacturer/Pengilang:</span>
                        <span class="val-text">{{ $manufacturerInfo }}</span>
                    </div>

                    <div class="info-row">
                        <span class="bold-label">Raw Material/Bahan Ramuan:</span>
                        <span class="val-text">{{ $row['allergen_info'] }}</span>
                    </div>

                    <div class="info-row">
                        <span class="bold-label">Prod. Date/Tarikh Pemprosesan:</span>
                        <span class="val-text">{{ $row['production_date'] }}</span>
                    </div>

                    <div class="info-row">
                        <span class="bold-label">Expiry/Tarikh Tamat Tempoh:</span>
                        <span class="val-text">{{ $row['expiry_date'] }}</span>
                    </div>

                    <div class="info-row" style="margin-bottom: 0;">
                        <span class="bold-label">Net Weight/Berat Bersih:</span>
                        <span class="val-text">{{ $row['net_weight'] ?? '1KG/2KG/5KG' }}</span>
                    </div>
                </td>
                <td class="card-td-right">
                    <div style="margin-bottom: 4px;">
                        @if($row['halal_logo'])
                        <img src="{{ $row['halal_logo'] }}" alt="Halal" style="height: 55px; width: auto;">
                        @else
                        <div style="height: 55px; width: 55px; border: 1px dashed #ccc; border-radius: 50%; display: inline-block; line-height: 55px; font-size: 9px; color: #555;">LOGO</div>
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>
    @endforeach

</body>

</html>