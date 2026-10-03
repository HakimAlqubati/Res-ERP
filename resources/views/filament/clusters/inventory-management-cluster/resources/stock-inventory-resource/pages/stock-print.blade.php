<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Stocktake Worksheet</title>
    <style>
        /* Global Styles */
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 25px;
            padding: 0;
            background-color: #ffffff;
            color: #222;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Header Section */
        .worksheet-header {
            text-align: center;
            margin-bottom: 18px;
        }

        .company-name {
            font-size: 26px;
            font-weight: 700;
            color: #145a50;
            margin: 0 0 6px 0;
            letter-spacing: 0.3px;
        }

        .worksheet-title {
            font-size: 19px;
            font-weight: 700;
            color: #333333;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Metadata Info Box */
        .meta-box {
            width: 100%;
            border: 1.5px solid #145a50;
            background-color: #f3f7f6;
            padding: 12px 16px;
            margin-bottom: 18px;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            background: transparent;
            box-shadow: none;
        }

        .meta-table td {
            border: none;
            padding: 6px 8px;
            font-size: 14px;
            color: #111;
            vertical-align: middle;
        }

        .meta-table td.left-col {
            width: 42%;
        }

        .meta-table td.right-col {
            width: 58%;
        }

        .meta-label {
            font-weight: 700;
            color: #111;
        }

        /* Main Worksheet Table */
        .stock-table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
        }

        .stock-table th,
        .stock-table td {
            border: 1px solid #d6d9dc;
            padding: 10px 8px;
            font-size: 13px;
            vertical-align: middle;
        }

        .stock-table thead th {
            background-color: #145a50;
            color: #ffffff;
            font-weight: 700;
            text-transform: uppercase;
            text-align: center;
            line-height: 1.35;
            border-color: #2c6f65;
        }

        .stock-table tbody tr {
            background-color: #ffffff;
        }

        .stock-table tbody tr:hover {
            background-color: #f8faf9;
        }

        .text-center {
            text-align: center;
        }

        .text-left {
            text-align: left;
        }

        .write-line {
            display: inline-block;
            width: 85%;
            border-bottom: 1px solid #333;
            height: 14px;
        }

        /* Print Button */
        .print-actions {
            text-align: center;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .btn-print {
            background-color: #145a50;
            color: white;
            border: none;
            padding: 10px 22px;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
        }

        .btn-print:hover {
            background-color: #0e433b;
        }

        /* Print Styles */
        @media print {
            @page {
                margin: 12mm;
            }

            body {
                margin: 0;
                background: #ffffff;
            }

            .stock-table thead {
                display: table-header-group;
            }

            .stock-table tr {
                page-break-inside: avoid;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    @php
        $storeId = $storeId ?? (request('store_id') ?: getDefaultStore());
        $store = $store ?? ($storeId ? \App\Models\Store::find($storeId) : null);
        $category = $category ?? (request('category_id') ? \App\Models\Category::find(request('category_id')) : null);
        $inventoryService = new \App\Services\MultiProductsInventoryService(
            null,
            null,
            'all',
            $storeId
        );
    @endphp

    <!-- Header Section -->
    <header class="worksheet-header">
        <h1 class="company-name">{{ setting('company_name') ?? 'Yahala F&B SDN BHD' }}</h1>
        <h2 class="worksheet-title">STOCKTAKE WORKSHEET</h2>
    </header>

    <!-- Metadata Info Box -->
    <div class="meta-box">
        <table class="meta-table">
            <tr>
                <td class="left-col">
                    <span class="meta-label">Stocktake Date:</span> {{ request('date') ?: date('d / m / Y') }}
                </td>
                <td class="right-col">
                    <span class="meta-label">Zone / Storage Location:</span> {{ $store?->name ?? 'All Stores' }}{{ !empty($store?->location) ? ' (' . $store->location . ')' : '' }}
                </td>
            </tr>
            <tr>
                <td class="left-col">
                    <span class="meta-label">Count Started Time:</span> ____ : ____
                </td>
                <td class="right-col">
                    <span class="meta-label">Category:</span> {{ $category?->name ?? 'All Categories' }}
                </td>
            </tr>
            <tr>
                <td class="left-col">
                    <span class="meta-label">Sheet Ref No:</span> ST-______________________
                </td>
                <td class="right-col">
                    <span class="meta-label">Counted By:</span> ______________________
                </td>
            </tr>
        </table>
    </div>

    <!-- Table Section -->
    <table class="stock-table">
        <thead>
            <tr>
                <th style="width: 10%;">PRODUCT<br>CODE</th>
                <th style="width: 28%;">PRODUCT NAME (EN / AR)</th>
                <th style="width: 11%;">CATEGORY</th>
                <th style="width: 8%;">UNIT</th>
                <th style="width: 11%;">SYSTEM<br>QTY</th>
                <th style="width: 12%;">PHYSICAL<br>COUNT</th>
                <th style="width: 11%;">DIFFERENCE</th>
                <th style="width: 9%;">REMARKS</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $product)
                @php
                    $productInventory = collect($inventoryService->getInventoryForProduct($product->id))->keyBy('unit_id');
                @endphp
                @if (!empty($product->unitPrices) && $product->unitPrices->count() > 0)
                    @foreach ($product->unitPrices as $unit)
                        @php
                            $sysQty = $productInventory[$unit->unit_id]['remaining_qty']
                                ?? \App\Services\MultiProductsInventoryService::getRemainingQty($product->id, $unit->unit_id, $storeId);
                        @endphp
                        <tr>
                            <td class="text-left">{{ $product->code }}</td>
                            <td class="text-left">{{ $product->name }}</td>
                            <td class="text-center">{{ $product->category->name ?? '-' }}</td>
                            <td class="text-center">{{ $unit->unit->name ?? 'N/A' }}</td>
                            <td class="text-center">{{ $sysQty }}</td>
                            <td class="text-center"><span class="write-line"></span></td>
                            <td class="text-center"><span class="write-line"></span></td>
                            <td></td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td class="text-left">{{ $product->code }}</td>
                        <td class="text-left">{{ $product->name }}</td>
                        <td class="text-center">{{ $product->category->name ?? '-' }}</td>
                        <td class="text-center">-</td>
                        <td class="text-center">0</td>
                        <td class="text-center"><span class="write-line"></span></td>
                        <td class="text-center"><span class="write-line"></span></td>
                        <td></td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>

    <!-- Print Button -->
    <div class="print-actions no-print">
        <button onclick="window.print()" class="btn-print">
            🖨️ Print Report
        </button>
    </div>

</body>

</html>
