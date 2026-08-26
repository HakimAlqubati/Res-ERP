<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Stocktake Valuation Report</title>
    <style>
        * {
            font-family: 'examplefont', sans-serif !important;
        }

        body {
            font-family: 'examplefont', sans-serif !important;
            background-color: #ffffff;
            direction: ltr !important;
            width: 100%;
            font-size: 11px;
            color: #1f2937;
            margin: 0;
            padding: 0;
        }

        .header-table {
            width: 100%;
            margin-bottom: 15px;
            border-bottom: 2px solid #374151;
            padding-bottom: 8px;
        }

        .header-table td {
            vertical-align: middle;
        }

        .title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            color: #111827;
            margin: 0 0 5px 0;
        }

        .meta-info {
            font-size: 11px;
            text-align: center;
            color: #4b5563;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #d1d5db;
            padding: 5px 7px;
            font-size: 10px;
        }

        .data-table thead th {
            background-color: #f3f4f6;
            font-weight: bold;
            text-align: left;
            color: #111827;
        }

        .data-table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .font-bold {
            font-weight: bold;
        }

        .footer-row td {
            background-color: #e5e7eb;
            font-weight: bold;
            font-size: 11px;
            border-top: 2px solid #374151;
        }

        .logo {
            max-height: 45px;
            max-width: 120px;
        }
    </style>
</head>
<body>
    {{-- Header --}}
    <table class="header-table">
        <tr>
            <td style="width: 25%;">
                @if(setting('company_logo') && file_exists(public_path('storage/' . setting('company_logo'))))
                    <img src="{{ public_path('storage/' . setting('company_logo')) }}" alt="Logo" class="logo">
                @endif
            </td>
            <td style="width: 50%; text-align: center;">
                <div class="title">Stocktake Valuation Report</div>
                <div class="meta-info">
                    Store: <strong>{{ $reportData->storeName }}</strong>
                    &nbsp;|&nbsp;
                    Date: <strong>{{ $reportData->inventoryDate }}</strong>
                </div>
            </td>
            <td style="width: 25%; text-align: right; font-size: 9px; color: #6b7280;">
                Generated: {{ now()->format('Y-m-d H:i') }}
            </td>
        </tr>
    </table>

    {{-- Table Data --}}
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 15%;">Product Code</th>
                <th style="width: 33%;">Product Name</th>
                <th style="width: 10%;">Unit</th>
                <th style="width: 10%;" class="text-right">Qty Per Pack</th>
                <th style="width: 10%;" class="text-right">Physical Qty</th>
                <th style="width: 11%;" class="text-right">Unit Price</th>
                <th style="width: 11%;" class="text-right">Total Price</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($reportData->items as $item)
                <tr>
                    <td>{{ $item->productCode }}</td>
                    <td>{{ $item->productName }}</td>
                    <td>{{ $item->unitName }}</td>
                    <td class="text-right">{{ number_format($item->packageSize, 3) }}</td>
                    <td class="text-right">{{ formatQunantity($item->physicalQty) }}</td>
                    <td class="text-right">{{ formatMoneyWithCurrency($item->unitPrice) }}</td>
                    <td class="text-right font-bold">{{ formatMoneyWithCurrency($item->totalValue) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="footer-row">
                <td colspan="6" class="text-right">
                    Grand Total Price:
                </td>
                <td class="text-right">
                    {{ formatMoneyWithCurrency($reportData->grandTotalValue) }}
                </td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
