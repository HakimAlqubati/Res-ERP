<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>Order Items Stock Shortage Report</title>
    <style>
        @page {
            margin-top: 15mm;
            margin-bottom: 15mm;
            margin-left: 10mm;
            margin-right: 10mm;
            footer: page-footer;
        }

        body {
            font-family: 'cairo', sans-serif;
            direction: rtl;
            text-align: right;
            color: #1f2937;
            font-size: 11px;
            line-height: 1.4;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }

        /* Clean English Title Only */
        .header-box {
            text-align: center;
            margin-bottom: 18px;
            padding-bottom: 8px;
            border-bottom: 2px solid #1e293b;
        }

        .header-box h1 {
            font-family: sans-serif;
            font-size: 20px;
            font-weight: bold;
            color: #0f172a;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Main Table */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
        }

        table.data-table thead th {
            background-color: #1e293b;
            color: #ffffff;
            font-weight: bold;
            padding: 7px 5px;
            border: 1px solid #0f172a;
            text-align: center;
            font-size: 11px;
        }

        table.data-table tbody td {
            padding: 6px 5px;
            border: 1px solid #d1d5db;
            text-align: center;
            vertical-align: middle;
        }

        table.data-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .td-name {
            text-align: right !important;
            padding-right: 8px !important;
        }

        .badge-order {
            background-color: #e0e7ff;
            color: #3730a3;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 3px;
        }

        .badge-store {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: 600;
        }

        /* Footer Total Row */
        table.data-table tfoot td {
            background-color: #e2e8f0;
            font-weight: bold;
            padding: 7px 5px;
            border: 1px solid #cbd5e1;
            text-align: center;
            font-size: 11px;
        }

        /* Empty message */
        .empty-box {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
            padding: 25px;
            text-align: center;
            font-size: 13px;
            border-radius: 6px;
            margin-top: 30px;
        }

        /* Page Footer */
        .page-footer-content {
            width: 100%;
            border-top: 1px solid #e5e7eb;
            padding-top: 4px;
            font-size: 9px;
            color: #9ca3af;
        }
    </style>
</head>

<body>

    <!-- Header: English Title Only -->
    <div class="header-box">
     </div>

    <!-- Data Table -->
    @if (count($items) > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 4%;">#</th>
                    <th style="width: 9%;">رقم الطلبية</th>
                    <th style="width: 9%;">تاريخ الطلب</th>
                    <th style="width: 13%;">الفرع</th>
                    <th style="width: 10%;">كود الصنف</th>
                    <th style="width: 23%;">اسم الصنف</th>
                    <th style="width: 7%;">الوحدة</th>
                    <th style="width: 8%;">الكمية</th>
                    <th style="width: 8%;">رصيد المخزن</th>
                    <th style="width: 12%;">المخزن</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td><span class="badge-order">#{{ $item['order_id'] }}</span></td>
                        <td>{{ $item['order_date'] }}</td>
                        <td>{{ $item['branch_name'] }}</td>
                        <td style="font-family: monospace; font-size: 10px;">{{ $item['product_code'] }}</td>
                        <td class="td-name">{{ $item['product_name'] }}</td>
                        <td>{{ $item['unit_name'] }}</td>
                        <td>{{ number_format($item['available_quantity'], 2) }}</td>
                        <td>{{ number_format($item['remaining_quantity'], 2) }}</td>
                        <td class="badge-store">{{ $item['store_name'] }}</td>
                    </tr>
                @endforeach
            </tbody>
           
        </table>
    @else
        <div class="empty-box">
            <strong>No shortage found for orders in range.</strong>
            <p style="margin-top: 5px; font-size: 11px; color: #15803d;">
                جميع الكميات المطلوبة بعد التعديل متوفرة بالمخازن للطلبيات المحددة.
            </p>
        </div>
    @endif

    <!-- Footer Page Numbers for mPDF -->
    <htmlpagefooter name="page-footer">
        <table class="page-footer-content">
            <tr>
                <td style="width: 50%; text-align: right;">
                    Order Items Stock Shortage Report
                </td>
                <td style="width: 50%; text-align: left;">
                    {PAGENO} / {nbpg}
                </td>
            </tr>
        </table>
    </htmlpagefooter>

</body>

</html>
