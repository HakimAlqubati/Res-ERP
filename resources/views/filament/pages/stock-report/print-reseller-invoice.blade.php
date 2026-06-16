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
        .invoice-wrapper {
            background-color: #f3f4f6;
            padding: 20px;
        }
        
        #invoice {
            background: #ffffff;
            padding: 40px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            width: 210mm;
            min-height: 297mm;
            margin: auto;
            color: #333;
            display: flex;
            flex-direction: column;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            box-sizing: border-box;
        }

        /* --- رأس الفاتورة --- */
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .invoice-header .title h1 {
            font-size: 36px;
            font-weight: bold;
            color: #0d7c66;
            margin: 0 0 15px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
            line-height: 1;
        }

        .invoice-meta p {
            margin: 6px 0;
            font-size: 14px;
            color: #444;
        }

        .divider {
            height: 2px;
            background-color: #0d7c66;
            margin: 20px 0;
            width: 100%;
        }

        /* --- معلومات الفرع والدفع --- */
        .invoice-info {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 30px;
        }

        .invoice-info > div {
            flex: 1;
        }

        .invoice-info h3 {
            font-size: 16px;
            color: #0d7c66;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .invoice-info p {
            margin: 8px 0;
            font-size: 14px;
        }

        /* --- جدول المنتجات --- */
        #invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }

        #invoice-table th,
        #invoice-table td {
            border: 1px solid #e5e7eb;
            padding: 12px 15px;
        }

        #invoice-table thead {
            background-color: #0d7c66;
            color: #ffffff;
        }

        #invoice-table th {
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            border-color: #0d7c66;
        }

        #invoice-table td.text-center, #invoice-table th.text-center {
            text-align: center;
        }

        #invoice-table td.text-right, #invoice-table th.text-right {
            text-align: right;
        }

        /* --- تذييل الفاتورة --- */
        .invoice-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            font-size: 12px;
        }

        .footer-contact {
            display: flex;
            gap: 20px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #333;
        }

        .contact-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 1px solid #0d7c66;
            color: #0d7c66;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .contact-icon svg {
            width: 12px;
            height: 12px;
            fill: currentColor;
        }

        .footer-thanks {
            text-align: left;
            border-left: 1px dashed #ccc;
            padding-left: 20px;
        }

        .footer-thanks strong {
            color: #0d7c66;
            font-size: 14px;
            display: block;
            margin-bottom: 4px;
        }

        .footer-thanks span {
            color: #777;
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
            .invoice-wrapper {
                background-color: transparent !important;
                padding: 0 !important;
            }
            #invoice, #invoice * {
                visibility: visible !important;
            }
            #invoice {
                position: absolute;
                inset: 0;
                margin: 0;
                width: 100%;
                min-height: auto;
                padding: 0;
                border: none;
                box-shadow: none;
            }
            .no-print {
                display: none !important;
            }
            .divider {
                background-color: #0d7c66 !important;
                -webkit-print-color-adjust: exact;
            }
            #invoice-table thead, #invoice-table th {
                background-color: #0d7c66 !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact;
            }
            thead { display: table-header-group; }
            tfoot { display: table-footer-group; }
            tr { page-break-inside: avoid; }
            @page {
                margin: 10mm;
            }
        }
    </style>

    <div class="invoice-wrapper">
        {{-- الفاتورة --}}
        <div id="invoice" class="bg-white">
            {{-- Header --}}
            <div class="invoice-header">
                <div class="title">
                    <h1>INVOICE</h1>
                    <div class="invoice-meta">
                        <p>Invoice #: {{ $record->id }}</p>
                        <p>Date: {{ $record->sale_date }}</p>
                    </div>
                </div>
                <div class="logo">
                    <img src="{{ asset('/storage/' . setting('company_logo')) }}" alt="Company Logo" style="height:80px; object-fit:contain;">
                </div>
            </div>

            <div class="divider"></div>

            {{-- Branch & Payment Info --}}
            <div class="invoice-info">
                <div class="avoid-break">
                    <h3>Branch Info</h3>
                    <p><strong>Branch:</strong> {{ $record->branch->name }}</p>
                    <p><strong>Store:</strong> {{ $record->store->name }}</p>
                </div>
                <div class="avoid-break" style="text-align: right; /* to push payment summary alignment like in image if needed */">
                    <div style="display: inline-block; text-align: left;">
                        <h3>Payment Summary</h3>
                        <p><strong>Total:</strong> {{ formatMoneyWithCurrency($record->total_amount) }}</p>
                        <p><strong>Paid:</strong> {{ formatMoneyWithCurrency($record->total_paid) }}</p>
                        <p><strong>Remaining:</strong> {{ formatMoneyWithCurrency($record->remaining_amount) }}</p>
                    </div>
                </div>
            </div>

            {{-- Items Table --}}
            <table id="invoice-table" class="avoid-break">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="text-center">Unit</th>
                        <th class="text-center">Quantity</th>
                        <th class="text-center">Unit Price</th>
                        <th class="text-center">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($record->items as $item)
                        <tr>
                            <td>{{ $item->product->name }}</td>
                            <td class="text-center">{{ $item->unit->name ?? '-' }}</td>
                            <td class="text-center">{{ number_format($item->quantity, 4) }}</td>
                            <td class="text-center">{{ formatMoneyWithCurrency($item->unit_price) }}</td>
                            <td class="text-center">{{ formatMoneyWithCurrency($item->total_price) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="flex-grow: 1;"></div>
            
            <div class="divider"></div>

            {{-- Footer --}}
            <div class="invoice-footer avoid-break">
                <div class="footer-contact">
                    <div class="contact-item">
                        <div class="contact-icon">
                            <svg viewBox="0 0 24 24"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
                        </div>
                        <span>{{ setting('company_phone') ?? '+60 3-1234 5678' }}</span>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                        </div>
                        <span>{{ setting('company_email') ?? 'info@workbench.com' }}</span>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
                        </div>
                        <span>{{ setting('company_website') ?? 'www.workbench.com' }}</span>
                    </div>
                </div>
                <div class="footer-thanks">
                    <strong>Thank you for your business.</strong>
                    <span>This is a computer generated invoice.</span>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex justify-end gap-4 mt-8 no-print pb-4">
                <x-filament::button type="button" onclick="window.print()">🖨️ Print</x-filament::button>
                <x-filament::button type="button" color="gray" onclick="exportTableToExcel('invoice-table', 'Invoice_{{ $record->id }}')">📄 Export Excel</x-filament::button>
            </div>
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
