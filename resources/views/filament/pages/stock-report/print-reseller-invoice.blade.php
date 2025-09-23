<x-filament-panels::page>
    <style>
        /* --- إعداد الطباعة الأساسية --- */
        @page {
            size: A4 portrait;
            margin: 15mm;
        }

        /* تحسين الألوان للطباعة */
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        /* إخفاء العناصر غير الضرورية أثناء الطباعة */
        .no-print {
            display: inline-flex;
        }

        /* --- الحاوية الأساسية --- */
        #invoice {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: .5rem;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            max-width: 210mm;
            margin: auto;
        }

        /* --- رأس الفاتورة --- */
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #0d7c66;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .invoice-header h1 {
            font-size: 28px;
            color: #0d7c66;
            margin: 0;
        }

        .invoice-meta p {
            margin: 2px 0;
            font-size: 12px;
            color: #555;
        }

        /* --- معلومات الفرع والدفع --- */
        .invoice-info {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 20px;
        }

        .invoice-info div h3 {
            font-size: 14px;
            color: #0d7c66;
            margin-bottom: 5px;
        }

        /* --- جدول المنتجات --- */
        #invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        #invoice-table th,
        #invoice-table td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        #invoice-table thead {
            background-color: #0d7c66;
            color: #ffffff;
        }

        #invoice-table th {
            text-align: left;
        }

        #invoice-table td.text-center {
            text-align: center;
        }

        #invoice-table td.text-right {
            text-align: right;
        }

        /* --- فواصل الصفحات --- */
        .avoid-break {
            page-break-inside: avoid;
        }

        /* --- الطباعة فقط --- */
        @media print {
            body * {
                visibility: hidden !important;
            }
            #invoice, #invoice * {
                visibility: visible !important;
            }
            #invoice {
                position: absolute;
                inset: 0;
                margin: 0;
                width: auto;
                border: none;
                box-shadow: none;
            }
            .no-print {
                display: none !important;
            }
            h1 { font-size: 24px !important; }
            h3 { font-size: 13px !important; }
            thead { display: table-header-group; }
            tfoot { display: table-footer-group; }
            tr { page-break-inside: avoid; }
        }
    </style>

    {{-- الفاتورة --}}
    <div id="invoice" class="avoid-break">
        {{-- Header --}}
        <div class="invoice-header">
            <div class="invoice-meta">
                <h1>INVOICE</h1>
                <p>Invoice #: {{ $record->id }}</p>
                <p>Date: {{ $record->sale_date }}</p>
            </div>
            <div>
                <img src="{{ asset('/storage/' . setting('company_logo')) }}" alt="Company Logo" style="height:60px; object-fit:contain;">
            </div>
        </div>

        {{-- Branch & Payment Info --}}
        <div class="invoice-info text-sm">
            <div class="avoid-break">
                <h3>Branch Info</h3>
                <p><strong>Branch:</strong> {{ $record->branch->name }}</p>
                <p><strong>Store:</strong> {{ $record->store->name }}</p>
            </div>
            <div class="avoid-break">
                <h3>Payment Summary</h3>
                <p><strong>Total:</strong> {{ formatMoneyWithCurrency($record->total_amount) }}</p>
                <p><strong>Paid:</strong> {{ formatMoneyWithCurrency($record->total_paid) }}</p>
                <p><strong>Remaining:</strong> {{ formatMoneyWithCurrency($record->remaining_amount) }}</p>
            </div>
        </div>

        {{-- Items Table --}}
        <table id="invoice-table" class="avoid-break">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Unit</th>
                    <th class="text-center">Quantity</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($record->items as $item)
                    <tr>
                        <td>{{ $item->product->name }}</td>
                        <td>{{ $item->unit->name ?? '-' }}</td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-right">{{ formatMoneyWithCurrency($item->unit_price) }}</td>
                        <td class="text-right">{{ formatMoneyWithCurrency($item->total_price) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Action Buttons --}}
        <div class="flex justify-end gap-4 mt-6 no-print">
            <x-filament::button type="button" onclick="window.print()">🖨️ Print</x-filament::button>
            <x-filament::button type="button" color="gray" onclick="exportTableToExcel('invoice-table', 'Invoice_{{ $record->id }}')">📄 Export Excel</x-filament::button>
        </div>
    </div>

    {{-- JS Excel Export --}}
    <script>
        function exportTableToExcel(tableID, filename = '') {
            const dataType = 'application/vnd.ms-excel';
            const tableSelect = document.getElementById(tableID);
            const tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');
            filename = filename ? filename + '.xls' : 'invoice.xls';
            const downloadLink = document.createElement("a");
            document.body.appendChild(downloadLink);
            downloadLink.href = 'data:' + dataType + ', ' + tableHTML;
            downloadLink.download = filename;
            downloadLink.click();
            downloadLink.remove();
        }
    </script>
</x-filament-panels::page>
