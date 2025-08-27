<x-filament-panels::page>
    <style>
        /* طباعة نظيفة وجميلة */
        @page {
            size: A4;
            margin: 14mm;
        }

        /* تحسين الألوان في الطباعة */
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        /* إخفاء عناصر أثناء الطباعة */
        .no-print {
            display: inline-flex;
        }

        /* الحاوية الأساسية */
        #invoice {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: .5rem;
        }

        /* حدود الجدول وتباعد أنيق */
        #invoice-table th,
        #invoice-table td {
            border: 1px solid #e5e7eb;
        }

        /* ألوان الترويسة في الطباعة */
        #invoice-table thead tr {
            background: #0d7c66 !important;
            color: #ffffff !important;
        }

        /* حجم خط مناسب للطباعة */
        #invoice, #invoice * {
            font-size: 12px;
            line-height: 1.4;
        }

        /* جعل رقم الفاتورة والتاريخ واضحين */
        .invoice-meta p {
            margin: 0;
        }

        /* إدارة فواصل الصفحات */
        .avoid-break {
            page-break-inside: avoid;
        }

        /* نمط الطباعة فقط */
        @media print {
            /* أظهر فقط الفاتورة */
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
                border: none; /* نزيل الحدود الخارجية في الطباعة */
                box-shadow: none;
                width: auto;
            }

            /* إخفاء الأزرار تمامًا في الطباعة */
            .no-print {
                display: none !important;
            }

            /* تكبير بسيط للعناوين بالطباعة */
            h1 {
                font-size: 20px !important;
            }
            h3 {
                font-size: 14px !important;
            }

            /* منع انقسام رأس الجدول بين الصفحات */
            thead { display: table-header-group; }
            tfoot { display: table-footer-group; }
            tr { page-break-inside: avoid; }
        }
    </style>

    {{-- الفاتورة --}}
    <div id="invoice" class="bg-white p-8 shadow-lg rounded-lg border border-gray-200 w-full avoid-break">
        {{-- Header --}}
        <div class="flex justify-between items-center border-b pb-4 mb-6">
            <div class="flex flex-col gap-2 invoice-meta">
                <h1 class="text-3xl font-extrabold text-[#0d7c66]">INVOICE</h1>
                <p class="text-gray-600 text-sm">Invoice #{{ $record->id }}</p>
                <p class="text-gray-600 text-sm">Date: {{ $record->sale_date }}</p>
            </div>

            <div class="flex items-center gap-3">
                <img src="{{ asset('/storage/' . setting('company_logo')) }}" alt="Company Logo" class="h-16 object-contain">
            </div>
        </div>

        {{-- Client & Branch Info --}}
        <div class="grid grid-cols-2 gap-6 text-sm text-gray-700 mb-8">
            <div class="avoid-break">
                <h3 class="font-semibold text-[#0d7c66] mb-2">Branch Info</h3>
                <p><strong>Branch:</strong> {{ $record->branch->name }}</p>
                <p><strong>Store:</strong> {{ $record->store->name }}</p>
            </div>
            <div class="avoid-break">
                <h3 class="font-semibold text-[#0d7c66] mb-2">Payment Summary</h3>
                <p><strong>Total:</strong> {{ formatMoneyWithCurrency($record->total_amount) }}</p>
                <p><strong>Paid:</strong> {{ formatMoneyWithCurrency($record->total_paid) }}</p>
                <p><strong>Remaining:</strong> {{ formatMoneyWithCurrency($record->remaining_amount) }}</p>
            </div>
        </div>

        {{-- Items Table --}}
        <table id="invoice-table" class="w-full text-sm border border-collapse mb-6">
            <thead>
                <tr class="text-left">
                    <th class="p-2">Product</th>
                    <th class="p-2">Unit</th>
                    <th class="p-2 text-center">Quantity</th>
                    <th class="p-2 text-right">Unit Price</th>
                    <th class="p-2 text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($record->items as $item)
                    <tr>
                        <td class="p-2">{{ $item->product->name }}</td>
                        <td class="p-2">{{ $item->unit->name ?? '-' }}</td>
                        <td class="p-2 text-center">{{ $item->quantity }}</td>
                        <td class="p-2 text-right">{{ formatMoneyWithCurrency($item->unit_price) }}</td>
                        <td class="p-2 text-right">{{ formatMoneyWithCurrency($item->total_price) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Action Buttons (لن تظهر عند الطباعة) --}}
        <div class="flex justify-end gap-4 mt-6 no-print">
            <x-filament::button type="button" onclick="window.print()">
                🖨️ Print
            </x-filament::button>
            <x-filament::button type="button" color="gray" onclick="exportTableToExcel('invoice-table', 'Invoice_{{ $record->id }}')">
                📄 Export Excel
            </x-filament::button>
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
