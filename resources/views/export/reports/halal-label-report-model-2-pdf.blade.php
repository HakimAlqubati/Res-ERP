<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Halal Label Artwork</title>
    <style>
        body {
            font-family: 'Arial', sans-serif !important;
            font-size: 11px;
            /* Slightly reduced font size to fit more content if needed */
            color: black;
            background-color: #ffffff;
            direction: ltr !important;
            margin: 5px;
            /* Added small margin to the whole page instead of relying solely on mPDF padding */
        }

        .label-title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .grid-table {
            width: 100%;
            border-collapse: collapse;
            border-top: 1px solid black;
            border-left: 1px solid black;
        }

        .grid-td {
            width: 50%;
            border-right: 1px solid black;
            border-bottom: 1px solid black;
            padding: 16px;
            vertical-align: top;
            height: 250px;
            box-sizing: border-box;
        }

        .inner-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
        }

        .inner-td-left {
            width: 75%;
            vertical-align: top;
            border: none;
            padding: 0;
            padding-right: 15px;
        }

        .inner-td-right {
            width: 25%;
            vertical-align: bottom;
            text-align: center;
            border: none;
            padding: 0;
        }

        .info-row {
            margin-bottom: 12px;
            line-height: 1.4;
        }

        .bold-label {
            font-weight: bold;
            display: block;
        }

        .bold-label-inline {
            font-weight: bold;
        }

        .product-name-val {
            font-weight: bold;
            font-size: 16px;
            text-transform: uppercase;
            display: block;
            margin-top: 2px;
        }

        .val-text {
            display: block;
            margin-top: 2px;
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

    <div class="label-title">
        LABEL ARTWORK<br>
        (LABEL PEMBUNGKUSAN) - STICKER
    </div>

    <table class="grid-table">
        <tr>
            @foreach ($reportData as $index => $row)
            @if($index > 0 && $index % 2 == 0)
        </tr>
        <tr>
            @endif

            <td class="grid-td">
                <table class="inner-table" style="height: 100%;">
                    <tr>
                        <td class="inner-td-left">
                            <div class="info-row">
                                <span class="bold-label">Product Name/Nama Produk:</span>
                                <span class="product-name-val">{{ $row['product_name'] }}</span>
                            </div>

                            <div class="info-row">
                                <span class="bold-label">Manufacturer Information/Maklumat Pengilang:</span>
                                <span class="val-text">{{ $manufacturerInfo }}</span>
                            </div>

                            <div class="info-row">
                                <span class="bold-label">Raw Material Information/Maklumat Bahan Ramuan:</span>
                                <span class="val-text">{{ $row['allergen_info'] }}</span>
                            </div>

                            <div class="info-row">
                                <span class="bold-label-inline">Production Date/Tarikh Pemprosesan:</span>
                                <span class="val-text">{{ $row['production_date'] }}</span>
                            </div>

                            <div class="info-row">
                                <span class="bold-label-inline">Expiry Date/Tarikh Tamat Tempoh:</span>
                                <span class="val-text">{{ $row['expiry_date'] }}</span>
                            </div>

                            <div class="info-row" style="margin-bottom: 0;">
                                <span class="bold-label-inline">Net Weight/Berat Bersih:</span>
                                <span class="val-text">{{ $row['net_weight'] ?? '1KG/2KG/5KG' }}</span>
                            </div>
                        </td>
                        <td class="inner-td-right">
                            <div style="margin-bottom: 5px;">
                                @if($row['halal_logo'])
                                @php
                                // ensure absolute path or base64 for mPDF, but often it can read remote URLs or local asset urls if configured. Assuming $row['halal_logo'] is public URL. If it's slow, we might need public_path().
                                // In standard Laravel mPDF, local URLs work.
                                @endphp
                                <img src="{{ $row['halal_logo'] }}" alt="Halal" style="height: 65px; width: auto;">
                                @else
                                <div style="height: 65px; width: 65px; border: 1px dashed #ccc; border-radius: 50%; display: inline-block; line-height: 65px; font-size: 10px; color: #555;">LOGO</div>
                                @endif
                            </div>
                            <div style="font-size: 10px; font-weight: bold; line-height: 1.2;">
                                MS1500<br>
                                {{ $row['patch_number'] ?? '' }}
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
            @endforeach

            {{-- Fill empty cell if odd number of items --}}
            @if(count($reportData) % 2 != 0)
            <td class="grid-td" style="border-right: 1px solid black;"></td>
            @endif
        </tr>
    </table>

</body>

</html>