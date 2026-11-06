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

        /* Action Bar & Buttons (Browser Preview only) */
        .action-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            gap: 12px;
            flex-wrap: wrap;
        }

        .action-bar-info {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .action-bar-title {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
        }

        .action-badge {
            display: inline-block;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 4px;
            background-color: #f1f5f9;
            color: #334155;
            border: 1px solid #e2e8f0;
        }

        .action-badge-orders {
            background-color: #eff6ff;
            color: #1d4ed8;
            border-color: #bfdbfe;
        }

        .action-bar-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-family: inherit;
            font-size: 12px;
            font-weight: 600;
            padding: 7px 14px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease-in-out;
        }

        .btn-excel {
            background-color: #107c41;
            color: #ffffff;
            box-shadow: 0 1px 2px rgba(16, 124, 65, 0.2);
        }

        .btn-excel:hover {
            background-color: #0d6535;
            box-shadow: 0 2px 5px rgba(16, 124, 65, 0.35);
        }

        .btn-print {
            background-color: #475569;
            color: #ffffff;
        }

        .btn-print:hover {
            background-color: #334155;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background-color: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
        }

        @media screen {
            htmlpagefooter {
                display: none;
            }
            body {
                padding: 20px;
                background-color: #f8fafc;
            }
            .report-content-wrapper {
                max-width: 1350px;
                margin: 0 auto;
                background: #ffffff;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            }
        }
    </style>
</head>

<body>

    <div class="report-content-wrapper">
        <!-- Action Bar (Hidden on Print & PDF) -->
        <div class="no-print action-bar">
            <div class="action-bar-info">
                <span class="action-bar-title">تقرير عجز الأصناف للطلبيات</span>
                @if (!empty($from_order) && !empty($to_order))
                    <span class="action-badge action-badge-orders">الطلبيات من #{{ $from_order }} إلى #{{ $to_order }}</span>
                @elseif (!empty($target_order))
                    <span class="action-badge action-badge-orders">طلبية رقم #{{ $target_order->id }}</span>
                @endif
                <span class="action-badge">عدد الأصناف المتأثرة: {{ count($items) }}</span>
            </div>

            <div class="action-bar-actions">
                @if (count($items) > 0)
                    <button type="button" onclick="exportToExcel()" class="btn-action btn-excel" id="btn-export-excel" title="تصدير إلى ملف إكسل">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <line x1="10" y1="9" x2="8" y2="9"></line>
                        </svg>
                        تصدير إكسل (Excel)
                    </button>
                @endif

                <button type="button" onclick="window.print()" class="btn-action btn-print" title="طباعة التقرير">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                    طباعة
                </button>
            </div>
        </div>

        <!-- Header: English Title Only -->
        <div class="header-box">
        </div>

        <!-- Data Table -->
        @if (count($items) > 0)
            <table id="shortage-report-table" class="data-table">
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
    </div>

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

    {{-- SheetJS Excel Export Library & Script --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        function exportToExcel() {
            if (typeof XLSX === 'undefined') {
                loadXlsxLibrary(function() {
                    performExcelExport();
                });
                return;
            }
            performExcelExport();
        }

        function loadXlsxLibrary(callback) {
            var script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js';
            script.onload = function() {
                if (typeof callback === 'function') callback();
            };
            script.onerror = function() {
                var fallback = document.createElement('script');
                fallback.src = 'https://unpkg.com/xlsx@0.18.5/dist/xlsx.full.min.js';
                fallback.onload = function() {
                    if (typeof callback === 'function') callback();
                };
                fallback.onerror = function() {
                    alert('تعذر تحميل مكتبة تصدير الإكسل. يرجى التحقق من اتصال الإنترنت.');
                };
                document.head.appendChild(fallback);
            };
            document.head.appendChild(script);
        }

        function performExcelExport() {
            var table = document.getElementById('shortage-report-table');
            if (!table) {
                alert('لا توجد بيانات متاحة للتصدير.');
                return;
            }

            // Clone table to safely manipulate contents without altering the DOM
            var clone = table.cloneNode(true);

            // Remove any elements that shouldn't appear in export
            clone.querySelectorAll('.no-print, button').forEach(function(el) {
                el.remove();
            });

            // Convert to SheetJS worksheet with raw values
            var ws = XLSX.utils.table_to_sheet(clone, { raw: true });

            // Process cells: format numbers cleanly and preserve strings
            for (var cellRef in ws) {
                if (cellRef[0] === '!') continue;

                var colLetter = cellRef.replace(/[0-9]/g, '');
                var rowNumber = parseInt(cellRef.replace(/[^0-9]/g, ''), 10);

                if (rowNumber > 1) { // Skip header row
                    // Column A (#) & Column B (Order ID)
                    if (colLetter === 'A' || colLetter === 'B') {
                        var cleanVal = String(ws[cellRef].v).replace('#', '').trim();
                        var intVal = parseInt(cleanVal, 10);
                        if (!isNaN(intVal) && String(intVal) === cleanVal) {
                            ws[cellRef].t = 'n';
                            ws[cellRef].v = intVal;
                        }
                    }
                    // Column H (Available Quantity) & Column I (Stock Remaining)
                    else if (colLetter === 'H' || colLetter === 'I') {
                        var cleanNum = parseFloat(String(ws[cellRef].v).replace(/,/g, '').trim());
                        if (!isNaN(cleanNum)) {
                            ws[cellRef].t = 'n';
                            ws[cellRef].v = cleanNum;
                        }
                    }
                }
            }

            // Define column widths
            ws['!cols'] = [
                { wch: 6 },  // #
                { wch: 14 }, // رقم الطلبية
                { wch: 14 }, // تاريخ الطلب
                { wch: 22 }, // الفرع
                { wch: 16 }, // كود الصنف
                { wch: 36 }, // اسم الصنف
                { wch: 12 }, // الوحدة
                { wch: 14 }, // الكمية
                { wch: 14 }, // رصيد المخزن
                { wch: 20 }  // المخزن
            ];

            // Set Right-to-Left sheet direction for Arabic readability
            ws['!views'] = [{ RTL: true }];

            var wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "عجز الأصناف");

            @php
                $filenameSuffix = '';
                if (!empty($from_order) && !empty($to_order)) {
                    $filenameSuffix = "_{$from_order}_to_{$to_order}";
                } elseif (!empty($target_order?->id)) {
                    $filenameSuffix = "_order_{$target_order->id}";
                }
            @endphp
            var fileName = "Order_Shortage_Report{{ $filenameSuffix }}_" + new Date().toISOString().slice(0, 10) + ".xlsx";

            XLSX.writeFile(wb, fileName);
        }
    </script>
</body>

</html>
