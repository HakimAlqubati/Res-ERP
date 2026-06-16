<x-filament-panels::page>
    <style>
        /* Shared Print Styles */
        @page { size: A4 portrait; margin: 15mm; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; color-adjust: exact !important; }
        .no-print { display: inline-flex; }
        .avoid-break { page-break-inside: avoid; }
        
        .invoice-wrapper { background-color: #f3f4f6; padding: 20px; margin-bottom: 20px; border-radius: 8px; }
        
        .invoice-container {
            background: #ffffff; padding: 40px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px; line-height: 1.6; width: 210mm; min-height: 297mm; margin: auto;
            color: #333; display: flex; flex-direction: column; box-shadow: 0 4px 6px rgba(0,0,0,0.1); box-sizing: border-box;
        }

        /* --- Template 1 Styles --- */
        #template-1 .invoice-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
        #template-1 .invoice-header .title h1 { font-size: 36px; font-weight: bold; color: #0d7c66; margin: 0 0 15px 0; text-transform: uppercase; letter-spacing: 1px; line-height: 1; }
        #template-1 .invoice-meta p { margin: 6px 0; font-size: 14px; color: #444; }
        #template-1 .divider { height: 2px; background-color: #0d7c66; margin: 20px 0; width: 100%; }
        #template-1 .invoice-info { display: flex; justify-content: space-between; gap: 20px; margin-bottom: 30px; }
        #template-1 .invoice-info > div { flex: 1; }
        #template-1 .invoice-info h3 { font-size: 16px; color: #0d7c66; margin-bottom: 15px; font-weight: 600; }
        #template-1 .invoice-info p { margin: 8px 0; font-size: 14px; }
        #template-1 #invoice-table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        #template-1 #invoice-table th, #template-1 #invoice-table td { border: 1px solid #e5e7eb; padding: 12px 15px; }
        #template-1 #invoice-table thead { background-color: #0d7c66; color: #ffffff; }
        #template-1 #invoice-table th { text-align: left; font-weight: 600; font-size: 14px; border-color: #0d7c66; }
        #template-1 #invoice-table td.text-center, #template-1 #invoice-table th.text-center { text-align: center; }
        #template-1 #invoice-table td.text-right, #template-1 #invoice-table th.text-right { text-align: right; }
        #template-1 .invoice-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 10px; font-size: 12px; }
        #template-1 .footer-contact { display: flex; gap: 20px; }
        #template-1 .contact-item { display: flex; align-items: center; gap: 8px; color: #333; }
        #template-1 .contact-icon { width: 24px; height: 24px; border-radius: 50%; border: 1px solid #0d7c66; color: #0d7c66; display: flex; align-items: center; justify-content: center; }
        #template-1 .contact-icon svg { width: 12px; height: 12px; fill: currentColor; }
        #template-1 .footer-thanks { text-align: left; border-left: 1px dashed #ccc; padding-left: 20px; }
        #template-1 .footer-thanks strong { color: #0d7c66; font-size: 14px; display: block; margin-bottom: 4px; }
        #template-1 .footer-thanks span { color: #777; }

        /* --- Template 2 Styles --- */
        #template-2 .invoice-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        #template-2 .invoice-header h1 { font-size: 28px; font-weight: bold; color: #0d7c66; margin: 0; text-transform: uppercase; letter-spacing: 1px; }
        #template-2 .info-box { border: 2px solid #0d7c66; border-radius: 12px; padding: 20px; display: flex; margin-bottom: 20px; }
        #template-2 .info-box-col { flex: 1; }
        #template-2 .info-box-col:first-child { border-right: 1px solid #e5e7eb; padding-right: 20px; margin-right: 20px; }
        #template-2 .info-item { display: flex; align-items: center; margin-bottom: 12px; font-size: 13px; }
        #template-2 .info-item:last-child { margin-bottom: 0; }
        #template-2 .info-icon { width: 20px; height: 20px; color: #0d7c66; margin-right: 15px; display: flex; align-items: center; justify-content: center; }
        #template-2 .info-icon svg { width: 18px; height: 18px; fill: currentColor; }
        #template-2 .info-label { width: 120px; color: #555; }
        #template-2 .info-value { font-weight: 600; color: #333; flex: 1; }
        
        #template-2 .divider { height: 2px; background-color: #0d7c66; margin: 20px 0; width: 100%; }
        
        #template-2 #invoice-table-2 { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        #template-2 #invoice-table-2 th, #template-2 #invoice-table-2 td { border: 1px solid #e5e7eb; padding: 12px 15px; }
        #template-2 #invoice-table-2 thead { background-color: #0d7c66; color: #ffffff; }
        #template-2 #invoice-table-2 th { text-align: left; font-weight: 600; font-size: 13px; border-color: #0d7c66; }
        #template-2 #invoice-table-2 td.text-center, #template-2 #invoice-table-2 th.text-center { text-align: center; }
        #template-2 #invoice-table-2 td.text-right, #template-2 #invoice-table-2 th.text-right { text-align: right; }
        
        #template-2 .totals-box-container { display: flex; justify-content: flex-end; margin-bottom: 30px; }
        #template-2 .totals-box { border: 1px solid #e5e7eb; border-radius: 8px; width: 300px; padding: 15px; }
        #template-2 .totals-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 13px; color: #555; }
        #template-2 .totals-row.grand-total { border-top: 2px solid #0d7c66; padding-top: 12px; margin-top: 10px; margin-bottom: 0; font-weight: bold; color: #0d7c66; font-size: 15px; }
        
        #template-2 .notes-section { display: flex; align-items: flex-start; margin-bottom: 40px; }
        #template-2 .notes-icon { color: #0d7c66; margin-right: 10px; }
        #template-2 .notes-icon svg { width: 20px; height: 20px; fill: currentColor; }
        #template-2 .notes-content strong { color: #0d7c66; font-size: 15px; display: block; margin-bottom: 10px; }
        #template-2 .notes-content p { color: #555; font-size: 13px; margin: 0; }

        #template-2 .signatures { display: flex; justify-content: space-between; margin-bottom: 20px; margin-top: 40px;}
        #template-2 .signature-col { text-align: center; width: 30%; }
        #template-2 .signature-line { border-bottom: 2px solid #ccc; height: 40px; margin-bottom: 10px; }
        #template-2 .signature-label { display: inline-flex; align-items: center; justify-content: center; gap: 8px; font-size: 13px; color: #555; }
        #template-2 .signature-label svg { width: 16px; height: 16px; fill: currentColor; color: #0d7c66; }

        #template-2 .invoice-footer { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 10px; font-size: 12px; }
        #template-2 .footer-contact { display: flex; justify-content: space-between; width: 100%; }
        #template-2 .contact-item { display: flex; align-items: center; gap: 8px; color: #555; }
        #template-2 .contact-item svg { width: 16px; height: 16px; fill: currentColor; color: #0d7c66; }
        #template-2 .footer-address { display: flex; align-items: flex-start; gap: 8px; color: #555; }
        #template-2 .footer-address svg { width: 16px; height: 16px; fill: currentColor; color: #0d7c66; margin-top: 2px; }
        #template-2 .footer-address p { margin: 0; line-height: 1.4; }

        /* Print Media Queries */
        @media print {
            body * { visibility: hidden !important; }
            .invoice-wrapper { background-color: transparent !important; padding: 0 !important; margin: 0 !important; }
            
            #template-1.print-active, #template-1.print-active * { visibility: visible !important; }
            #template-2.print-active, #template-2.print-active * { visibility: visible !important; }
            
            #template-1.print-active .invoice-container, #template-2.print-active .invoice-container {
                position: absolute; inset: 0; margin: 0; width: 100%; min-height: auto; padding: 0; border: none; box-shadow: none;
            }
            .no-print { display: none !important; }
            
            /* Template 1 specific print fixes */
            #template-1 .divider { background-color: #0d7c66 !important; -webkit-print-color-adjust: exact; }
            #template-1 #invoice-table thead, #template-1 #invoice-table th { background-color: #0d7c66 !important; color: #ffffff !important; -webkit-print-color-adjust: exact; }
            
            /* Template 2 specific print fixes */
            #template-2 .info-box { border-color: #0d7c66 !important; -webkit-print-color-adjust: exact; }
            #template-2 .totals-box { border-color: #e5e7eb !important; -webkit-print-color-adjust: exact; }
            #template-2 .divider { background-color: #0d7c66 !important; -webkit-print-color-adjust: exact; }
            #template-2 .totals-row.grand-total { border-top-color: #0d7c66 !important; color: #0d7c66 !important; -webkit-print-color-adjust: exact; }
            #template-2 #invoice-table-2 thead, #template-2 #invoice-table-2 th { background-color: #0d7c66 !important; color: #ffffff !important; -webkit-print-color-adjust: exact; }
            #template-2 .signature-line { border-bottom-color: #ccc !important; -webkit-print-color-adjust: exact; }
            
            @page { margin: 10mm; }
        }
    </style>

    <!-- Template 1 -->
    <div class="invoice-wrapper print-active" id="template-1">
        <div class="invoice-container">
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
                <div class="avoid-break" style="text-align: right;">
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
            <div class="flex justify-between items-center mt-8 no-print pb-4 border-t pt-4">
                <x-filament::button type="button" color="secondary" onclick="switchTemplate('2')">👁️ View Template 2</x-filament::button>
                <div class="flex gap-4">
                    <x-filament::button type="button" onclick="window.print()">🖨️ Print Active</x-filament::button>
                    <x-filament::button type="button" color="gray" onclick="exportTableToExcel('invoice-table', 'Invoice_{{ $record->id }}')">📄 Export Excel</x-filament::button>
                </div>
            </div>
        </div>
    </div>

    <!-- Template 2 -->
    <div class="invoice-wrapper" id="template-2" style="display: none;">
        <div class="invoice-container">
            {{-- Header --}}
            <div class="invoice-header">
                <div class="logo">
                    <img src="{{ asset('/storage/' . setting('company_logo')) }}" alt="Company Logo" style="height:80px; object-fit:contain;">
                </div>
                <div class="title">
                    <h1>SALES INVOICE</h1>
                </div>
            </div>

            {{-- Info Box --}}
            <div class="info-box avoid-break">
                <div class="info-box-col">
                    <div class="info-item">
                        <div class="info-icon"><svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg></div>
                        <div class="info-label">Invoice No</div>
                        <div class="info-value">: {{ $record->id }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon"><svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/></svg></div>
                        <div class="info-label">Date</div>
                        <div class="info-value">: {{ $record->sale_date }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon"><svg viewBox="0 0 24 24"><path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/></svg></div>
                        <div class="info-label">Branch</div>
                        <div class="info-value">: {{ $record->branch->name }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon"><svg viewBox="0 0 24 24"><path d="M20 4H4v2h16V4zm1 10v-2l-1-5H4l-1 5v2h1v6h10v-6h4v6h2v-6h1zm-9 4H6v-4h6v4z"/></svg></div>
                        <div class="info-label">Store</div>
                        <div class="info-value">: {{ $record->store->name }}</div>
                    </div>
                </div>
                <div class="info-box-col">
                    <div class="info-item" style="margin-bottom: 20px;">
                        <div class="info-icon"><svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg></div>
                        <div class="info-label" style="width: auto; margin-right: 20px;">Payment Summary</div>
                        <div class="info-value">: </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon"><svg viewBox="0 0 24 24"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg></div>
                        <div class="info-label">Total</div>
                        <div class="info-value">: {{ formatMoneyWithCurrency($record->total_amount) }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon"><svg viewBox="0 0 24 24"><path d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg></div>
                        <div class="info-label">Paid</div>
                        <div class="info-value">: {{ formatMoneyWithCurrency($record->total_paid) }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg></div>
                        <div class="info-label">Remaining</div>
                        <div class="info-value">: {{ formatMoneyWithCurrency($record->remaining_amount) }}</div>
                    </div>
                </div>
            </div>

            <div class="divider"></div>

            {{-- Items Table --}}
            <table id="invoice-table-2" class="avoid-break">
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

            {{-- Notes & Totals --}}
            <div class="avoid-break" style="display: flex; justify-content: space-between; margin-bottom: 30px; margin-top: 20px;">
                <div class="notes-section" style="flex: 1;">
                    <div class="notes-icon">
                        <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                    </div>
                    <div class="notes-content">
                        <strong>Notes</strong>
                        <p>Thank you for your business.</p>
                    </div>
                </div>
                
                <div class="totals-box-container" style="margin-bottom: 0;">
                    <div class="totals-box">
                        <div class="totals-row">
                            <span>Total</span>
                            <span>{{ formatMoneyWithCurrency($record->total_amount) }}</span>
                        </div>
                        <div class="totals-row">
                            <span>Paid</span>
                            <span>{{ formatMoneyWithCurrency($record->total_paid) }}</span>
                        </div>
                        <div class="totals-row">
                            <span>Remaining</span>
                            <span>{{ formatMoneyWithCurrency($record->remaining_amount) }}</span>
                        </div>
                        <div class="totals-row grand-total">
                            <span>Grand Total</span>
                            <span>{{ formatMoneyWithCurrency($record->total_amount) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div style="flex-grow: 1;"></div>
            
            {{-- Signatures --}}
            <div class="signatures avoid-break">
                <div class="signature-col">
                    <div class="signature-line"></div>
                    <div class="signature-label">
                        <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a.9959.9959 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                        Prepared By
                    </div>
                </div>
                <div class="signature-col">
                    <div class="signature-line"></div>
                    <div class="signature-label">
                        <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/></svg>
                        Approved By
                    </div>
                </div>
                <div class="signature-col">
                    <div class="signature-line"></div>
                    <div class="signature-label">
                        <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                        Customer Signature
                    </div>
                </div>
            </div>

            <div class="divider"></div>

            {{-- Footer --}}
            <div class="invoice-footer avoid-break">
                <div class="footer-contact">
                    <div class="footer-address">
                        <svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                        <p>Workbench ERP<br>Bukit Bintang, Kuala Lumpur</p>
                    </div>
                    <div class="contact-item">
                        <svg viewBox="0 0 24 24"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
                        <span>{{ setting('company_phone') ?? '+60 3-1234 5678' }}</span>
                    </div>
                    <div class="contact-item">
                        <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                        <span>{{ setting('company_email') ?? 'info@workbench.com' }}</span>
                    </div>
                    <div class="contact-item">
                        <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
                        <span>{{ setting('company_website') ?? 'www.workbench.com' }}</span>
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex justify-between items-center mt-8 no-print pb-4 border-t pt-4">
                <x-filament::button type="button" color="secondary" onclick="switchTemplate('1')">👁️ View Template 1</x-filament::button>
                <div class="flex gap-4">
                    <x-filament::button type="button" onclick="window.print()">🖨️ Print Active</x-filament::button>
                    <x-filament::button type="button" color="gray" onclick="exportTableToExcel('invoice-table-2', 'Invoice_{{ $record->id }}')">📄 Export Excel</x-filament::button>
                </div>
            </div>
        </div>
    </div>

    {{-- JS Template Switching & Excel Export --}}
    <script>
        function switchTemplate(template) {
            if(template === '1') {
                document.getElementById('template-1').style.display = 'block';
                document.getElementById('template-1').classList.add('print-active');
                document.getElementById('template-2').style.display = 'none';
                document.getElementById('template-2').classList.remove('print-active');
            } else {
                document.getElementById('template-1').style.display = 'none';
                document.getElementById('template-1').classList.remove('print-active');
                document.getElementById('template-2').style.display = 'block';
                document.getElementById('template-2').classList.add('print-active');
            }
        }

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
