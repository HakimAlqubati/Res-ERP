<x-filament::page>
    <style>
        .fi-tabs {
            display: none !important;
        }

        .grid-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            border-top: 1px solid black;
            border-left: 1px solid black;
            width: 100%;
            background-color: white;
        }

        .grid-item {
            border-right: 1px solid black;
            border-bottom: 1px solid black;
            padding: 12px;
            box-sizing: border-box;
            page-break-inside: avoid;
            break-inside: avoid;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .label-title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            font-family: Arial, Helvetica, sans-serif;
            margin-bottom: 16px;
        }

        .content-text {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: black;
        }

        .bold-label {
            font-weight: bold;
        }

        .product-name-val {
            font-weight: bold;
            font-size: 18px;
            text-transform: uppercase;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .fi-main-content {
                padding: 0 !important;
                margin: 0 !important;
                background-color: white !important;
            }

            body {
                background-color: white !important;
            }
        }
    </style>

    {{ $this->getTableFiltersForm() }}

    <div id="reportContent" class="mt-6 bg-white p-4">
        @if (empty($store))
        <div class="please_select_message_div text-center">
            <h1 class="please_select_message_text">Please select a store.</h1>
        </div>
        @elseif (empty($reportData))
        <div class="please_select_message_div text-center">
            <h1 class="please_select_message_text">No halal label data found for store ({{ $store }}).</h1>
        </div>
        @else

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

        <div class="grid-container">
            @foreach ($reportData as $row)
            <div class="grid-item" style="position: relative; padding: 16px; min-height: 250px;">
                <div class="content-text" style="padding-right: 120px;">
                    <div style="margin-bottom: 24px;">
                        <span class="bold-label">Product Name/Nama Produk:</span>
                        <span class="product-name-val">{{ $row['product_name'] }}</span>
                    </div>

                    <div style="margin-bottom: 24px;">
                        <span class="bold-label">Manufacturer Information/Maklumat Pengilang:</span>
                        <span style="display: inline;">{{ $manufacturerInfo }}</span>
                    </div>

                    <div style="margin-bottom: 24px;">
                        <span class="bold-label">Raw Material Information/Maklumat Bahan Ramuan:</span>
                        <span style="display: inline;">{{ $row['allergen_info'] }}</span>
                    </div>

                    <div style="margin-bottom: 24px;">
                        <span class="bold-label">Production Date/Tarikh Pemprosesan:</span>
                        <span style="display: inline;">{{ $row['production_date'] }}</span>
                    </div>

                    <div style="margin-bottom: 24px;">
                        <span class="bold-label">Expiry Date/Tarikh Tamat Tempoh:</span>
                        <span style="display: inline;">{{ $row['expiry_date'] }}</span>
                    </div>

                    <div style="margin-bottom: 4px;">
                        <span class="bold-label">Net Weight/Berat Bersih:</span>
                        <span style="display: inline;">{{ $row['net_weight'] ?? '1KG/2KG/5KG' }}</span>
                    </div>
                </div>

                <div style="position: absolute; right: 16px; bottom: 16px; width: 100px; text-align: center;">
                    <div style="margin-bottom: 6px; display: flex; justify-content: center;">
                        @if($row['halal_logo'])
                        <img src="{{ $row['halal_logo'] }}" alt="Halal" style="height: 65px; width: auto; object-fit: contain;">
                        @else
                        <div style="height: 65px; width: 65px; border: 1px dashed #ccc; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #555;">LOGO</div>
                        @endif
                    </div>

                    <div style="font-size: 11px; font-weight: bold; line-height: 1.2;">
                        MS1500<br>
                        X XXX-XX/XXXX
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</x-filament::page>