<x-filament::page>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        .report-container {
            max-width: 100%;
            margin: 0 auto;
            padding: 2rem;
            background: white;
            border: 2px solid #cbd5e1;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            width: 60%;
        }

        .report-header {

            /* padding: 1.25rem 1.5rem; */
            text-align: center;
            border-radius: 6px;
            /* margin-bottom: 1.5rem; */
        }

        .report-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 0 0.5rem 0;
        }

        .report-subtitle {
            font-size: 0.85rem;
            opacity: 0.95;
            /* margin-bottom: 0.25rem; */
        }

        .report-meta {
            font-size: 0.8rem;
            opacity: 0.85;
        }

        .report-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
            overflow: hidden;
        }

        .section-header {
            /* padding: 0.75rem 0 0.5rem 0; */
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #1f2937;
            border-bottom: 2px solid #e5e7eb;
        }


        .table-row:nth-child(even) {
            background-color: #f9fafb;
        }

        .table-row:hover {
            background-color: #eff6ff;
            transform: translateX(2px);
            /* box-shadow: inset 4px 0 0 #3b82f6; */
        }

        .row-label {
            padding: 0.5rem 0.5rem 0.5rem 0.5rem;
            font-size: 0.875rem;
            color: #1f2937;
            font-weight: 600;
            border-bottom: 0.5px solid #1c8237ff;

        }

        .row-value {
            padding: 0.5rem 1.25rem 0.5rem 0.5rem;
            text-align: right;
            font-size: 0.9rem;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            color: #0f172a;
            letter-spacing: 0.01em;
            border-bottom: 0.5px solid #1c8237ff;

        }

        .child-row {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            transition: all 0.2s ease;
        }

        .child-row:hover {
            background-color: #e7f0ff;
            transform: translateX(4px);
        }

        .child-label {
            padding: 0.75rem 1rem 0.75rem 3rem;
            font-size: 0.8125rem;
            color: #6b7280;
            font-weight: 500;
        }

        .child-value {
            padding: 0.75rem 1.25rem 0.75rem 1rem;
            text-align: right;
            font-size: 0.8125rem;
            color: #4b5563;
            font-weight: 600;
        }

        .revenue-amount {
            background: linear-gradient(90deg, transparent, #f0fdf4 100%);
            font-weight: 700;
        }

        .expense-amount {
            color: #dc2626;
            background: linear-gradient(90deg, transparent, #fef2f2 100%);
            font-weight: 700;
        }

        .total-row {
            background-color: #f8fafc;
            border-top: 2px solid #cbd5e1;
            border-bottom: 2px solid #cbd5e1;
        }

        .total-label {
            padding: 0.85rem 0 0.85rem 1rem;
            font-size: 0.9rem;
            font-weight: 700;
            color: #0f172a;
        }

        .total-value {
            padding: 0.85rem 1rem 0.85rem 0;
            text-align: right;
            font-size: 0.9rem;
            font-weight: 700;
            color: #0f172a;
        }

        .net-profit-section {
            /* margin-top: 1.5rem; */
            /* padding: 1rem 0; */
            border-top: 2px solid #cbd5e1;
            border-bottom: 2px solid #cbd5e1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .net-profit-label {
            font-size: 0.9rem;
            font-weight: 700;
            color: #0f172a;
            /* text-transform: uppercase; */
            letter-spacing: 0.05em;
        }

        .net-profit-amount {
            font-size: 1.0rem;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
        }

        .profit-positive {
            color: #059669;
        }

        .profit-negative {
            color: #dc2626;
        }

        .signatures {
            margin-top: 2.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 3rem;
        }

        .signature-box {
            flex: 1;
            padding-top: 1.5rem;
            border-top: 1px solid #cbd5e1;
        }

        .signature-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #94a3b8;
            letter-spacing: 0.05em;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            background: white;
            border-radius: 6px;
            border: 2px dashed #d1d5db;
        }

        .empty-text {
            font-size: 1rem;
            color: #6b7280;
            font-weight: 500;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: white !important;
            }

            .table-row:hover {
                background-color: transparent;
            }
        }

        @media (max-width: 768px) {
            .report-container {
                max-width: 100%;
                padding: 1rem;
            }

            .report-header {
                padding: 1rem;
            }

            .report-title {
                font-size: 1.25rem;
            }

            .signatures {
                flex-direction: column;
                gap: 2rem;
            }

            .net-profit-section {
                flex-direction: column;
                gap: 0.75rem;
                text-align: center;
            }
        }

        [x-cloak] {
            display: none !important;
        }

        /* Dark Mode Styles */
        .dark .report-container {
            background: #1e293b;
            border-color: #334155;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .dark .report-header {
            border-bottom-color: #475569;
        }

        .dark .report-title {
            color: #f1f5f9;
        }

        .dark .report-subtitle {
            color: #94a3b8;
        }

        .dark .section-header {
            color: #f1f5f9;
            border-bottom-color: #475569;
        }

        .dark .table-row {
            background-color: #1e293b;
        }

        .dark .table-row:nth-child(even) {
            background-color: #334155;
        }

        .dark .table-row:hover {
            background-color: #3b4f6b;
        }

        .dark .row-label {
            color: #f1f5f9;
            border-bottom-color: #10b981;
        }

        .dark .row-value {
            color: #e2e8f0;
            border-bottom-color: #10b981;
        }

        .dark .child-row {
            background-color: #0f172a;
            border-bottom-color: #334155;
        }

        .dark .child-row:hover {
            background-color: #1e3a5f;
        }

        .dark .child-label {
            color: #94a3b8;
        }

        .dark .child-value {
            color: #cbd5e1;
        }

        .dark .revenue-amount {
            background: linear-gradient(90deg, transparent, #064e3b 100%);
            color: #34d399;
        }

        .dark .expense-amount {
            color: #f87171;
            background: linear-gradient(90deg, transparent, #450a0a 100%);
        }

        .dark .total-row {
            background-color: #0f172a;
            border-top-color: #475569;
            border-bottom-color: #475569;
        }

        .dark .total-label,
        .dark .total-value {
            color: #f1f5f9;
        }

        .dark .net-profit-section {
            border-top-color: #475569;
            border-bottom-color: #475569;
            background-color: #0f172a;
        }

        .dark .net-profit-label {
            color: #f1f5f9;
        }

        .dark .profit-positive {
            color: #34d399;
        }

        .dark .profit-negative {
            color: #f87171;
        }

        .dark .signatures {
            border-top-color: #475569;
        }

        .dark .signature-box {
            border-top-color: #475569;
        }

        .dark .signature-label {
            color: #64748b;
        }

        .dark .empty-state {
            background: #1e293b;
            border-color: #475569;
        }

        .dark .empty-text {
            color: #94a3b8;
        }
    </style>

    {{-- Filters Form --}}
    <div class="no-print mb-6 bg-white p-4 rounded-lg shadow-sm border border-gray-200">
        {{ $this->getTableFiltersForm() }}
    </div>

    <div class="report-container">
        @if (isset($report) && !empty($report))
        {{-- Header --}}
        <div class="report-header" style="display: flex; justify-content: space-between;">
            {{-- Company Logo (Left) --}}
            <div style="flex: 0 0 80px;">
                <img src="{{ asset('/storage/' . setting('company_logo')) }}" alt="Company Logo"
                    style="height: 60px; width: auto; object-fit: contain; border-radius: 0%;">
            </div>

            {{-- Title (Center) --}}
            <div style="flex: 1; text-align: center;">
                <h1 class="report-title">{{ __('Gross Profit Report') }}</h1>
                <p class="report-subtitle">
                    @if($startDate && $endDate)
                    {{ __('From ') }} {{ \Carbon\Carbon::parse($startDate)->format('d F, Y') }}
                    {{ __('to') }} {{ \Carbon\Carbon::parse($endDate)->format('d F, Y') }}
                    @else
                    {{ __('All Time Records') }}
                    @endif
                </p>
                @if(isset($branchName) && $branchName)
                <p class="report-subtitle" style="font-weight: 600; margin-top: 0.25rem;">
                    {{ __('Branch') }}: {{ $branchName }}
                </p>
                @else
                <p class="report-subtitle" style="font-weight: 600; margin-top: 0.25rem;">
                    {{ __('Branch') }}: {{ __('All') }}
                </p>
                @endif
            </div>

            {{-- System Logo (Right) --}}
            <div style="flex: 0 0 80px; text-align: right;">
                <img src="{{ url('/') . '/storage/workbench.png' }}" alt="System Logo"
                    style="height: 40px; width: auto; object-fit: contain;">
            </div>
        </div>

        {{-- Report Table --}}
        <table class="report-table">
            <tbody>
                {{-- Revenue Section --}}
                <!-- <tr>
                    <td colspan="2" class="section-header">{{ __('Revenue') }}</td>
                </tr> -->
                <tr class="table-row">
                    <td class="row-label">
                        <div style="font-weight: 600;">{{ __('Total Sales Revenue') }}</div>
                        <div style="font-size: 0.7rem; color: #9ca3af; margin-top: 0rem;">
                            {{ 'Imported From POS' }}
                        </div>
                    </td>
                    <td class="row-value revenue-amount">
                        {{ $report['revenue']['total_formatted'] ?? number_format($report['revenue']['total'], 2) }}
                    </td>
                </tr>

                {{-- Cost of Goods Sold Components --}}
                @if(($report['cost_of_goods_sold']['closing_stock'] ?? 0) > 0)
                <tr class="table-row">
                    <td class="row-label">
                        <div style="font-weight: 600;">{{ __('Closing Stock') }}</div>
                        <div style="font-size: 0.7rem; color: #9ca3af; margin-top: 0rem;">
                            {{ __('End of Period Inventory') }}
                        </div>
                    </td>
                    <td class="row-value" style="color: #059669;">
                        {{ $report['cost_of_goods_sold']['closing_stock_formatted'] ?? number_format($report['cost_of_goods_sold']['closing_stock'] ?? 0, 2) }}
                    </td>
                </tr>
                @endif

                @if(($report['cost_of_goods_sold']['direct_purchase'] ?? 0) > 0)
                <tr class="table-row">
                    <td class="row-label">
                        <div style="font-weight: 600;">{{ __('Direct Purchase') }}</div>
                        <div style="font-size: 0.7rem; color: #9ca3af; margin-top: 0rem;">
                            {{ __('Direct Purchases for Branch') }}
                        </div>
                    </td>
                    <td class="row-value expense-amount">
                        ({{ $report['cost_of_goods_sold']['direct_purchase_formatted'] ?? number_format($report['cost_of_goods_sold']['direct_purchase'] ?? 0, 2) }})
                    </td>
                </tr>
                @endif

                {{-- Expenses Section --}}
                <!-- <tr>
                    <td colspan="2" class="section-header">{{ __('Expenses') }}</td>
                </tr> -->

                @foreach($report['expenses']['details'] as $expense)
                <tr class="table-row">
                    <td class="row-label">
                        <div style="font-weight: 600;">{{ $expense['category_name'] }}</div>
                        @if(!empty($expense['category_description']))
                        <div style="font-size: 0.7rem; color: #9ca3af; margin-top: 0rem;">
                            {{ $expense['category_description'] }}
                        </div>
                        @endif
                    </td>
                    <td class="row-value">
                        {{ $expense['amount_formatted'] ?? number_format($expense['amount'], 2) }}
                    </td>
                </tr>

                @if(!empty($expense['children']))
                @foreach($expense['children'] as $child)
                <tr class="child-row">
                    <td class="child-label">
                        <span style="color: #d1d5db; margin-right: 0.5rem;">└─</span>
                        {{ $child['category_name'] }}
                    </td>
                    <td class="child-value">
                        {{ $child['amount_formatted'] ?? number_format($child['amount'], 2) }}
                    </td>
                </tr>
                @endforeach
                @endif
                @endforeach


            </tbody>
        </table>

        {{-- Gross Profit --}}
        <div class="net-profit-section">
            <div class="net-profit-label">{{ __('Gross Profit') }}</div>
            <div class="net-profit-amount {{ ($report['gross_profit']['value'] ?? 0) >= 0 ? 'profit-positive' : 'profit-negative' }}">
                {{ $report['gross_profit']['value_formatted'] ?? number_format($report['gross_profit']['value'] ?? 0, 2) }}
            </div>
        </div>

        {{-- Cross Margin --}}
        <div class="net-profit-section">
            <div class="net-profit-label">{{ __('Gross Margin') }}</div>
            <div class="net-profit-amount {{ ($report['cross_margin']['value'] ?? 0) >= 0 ? 'profit-positive' : 'profit-negative' }}">
                <!-- {{ $report['cross_margin']['value_formatted'] ?? number_format($report['cross_margin']['value'] ?? 0, 2) }} -->
                <span style="font-size: 1rem; margin-left: 1rem; opacity: 0.8;"> {{ $report['gross_profit']['ratio_formatted'] ?? '0.00%' }} </span>
            </div>
        </div>

        {{-- Generated On --}}
        <div style="margin-top: 2.5rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb;">
            {{-- Formula --}}
            <p style="font-size: 0.7rem; color: #6b7280; margin: 0 0 0.5rem 0; font-weight: 500; text-align: center;">
                Gross Profit = Sales + Closing Stock – Transfers – Direct Purchases({{'if applicable'}})
            </p>
            {{-- Generated on + Powered by AWS (same row) --}}
            <div style="display: flex; align-items: center;">
                {{-- Empty left space --}}
                <div style="flex: 1;"></div>
                {{-- Center: Generated on --}}
                <div style="flex: 1; text-align: center;">
                    <p style="font-size: 0.7rem; color: #6b7280; margin: 0; white-space: nowrap;">
                        {{ __('Generated on') }} {{ \Carbon\Carbon::now()->format('d F, Y - h:i A') }}
                    </p>
                </div>
                {{-- Right: Powered by AWS --}}
                <div style="flex: 1; display: flex; justify-content: flex-end; align-items: center; gap: 0.5rem;">
                    <span style="font-size: 0.7rem; color: #6b7280;">Powered by</span>
                    <img src="https://upload.wikimedia.org/wikipedia/commons/9/93/Amazon_Web_Services_Logo.svg"
                        alt="AWS Logo"
                        style="height: 18px; width: auto;">
                </div>
            </div>
        </div>
        @else
        <div class="empty-state">
            <p class="empty-text">{{ __('Please select filters to generate the report.') }}</p>
        </div>
        @endif
    </div>
</x-filament::page>