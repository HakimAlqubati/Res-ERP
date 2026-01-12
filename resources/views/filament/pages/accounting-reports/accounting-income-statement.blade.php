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
            width: 70%;
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 6px;
            margin-bottom: 2.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .header-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            border: 2px solid #a7f3d0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #10b981;
            background-color: #f0fdf4;
        }

        .report-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 0 0.5rem 0;
            color: #1e293b;
        }

        .report-subtitle {
            font-size: 0.85rem;
            color: #64748b;
        }

        .report-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
            overflow: hidden;
        }

        .section-header {
            padding: 1rem 0.5rem;
            font-size: 0.9rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #1e40af;
            border-bottom: 2px solid #e2e8f0;
            background-color: #f8fafc;
        }

        .table-row {
            border-bottom: 1px solid #f1f5f9;
        }

        .table-row:hover {
            background-color: #f1f5f9;
        }

        .td-label {
            padding: 1.25rem 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .main-label {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1e293b;
        }

        .sub-label {
            font-size: 0.75rem;
            color: #94a3b8;
            font-weight: 400;
        }

        .td-value {
            padding: 1rem 1rem;
            text-align: right;
            font-size: 1.15rem;
            color: #1e293b;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
        }

        .table-row {
            border-bottom: 1px solid #f1f5f9;
        }

        .table-row.highlight {
            background-color: #f0fdf4;
        }

        .table-row.normal {
            background-color: white;
        }

        .section-separator {
            border-top: 2px solid #10b981;
            margin-bottom: 0;
        }

        .revenue-total {
            background-color: #f0fdf4;
            border-top: 1px solid #bbf7d0;
            border-bottom: 1px solid #bbf7d0;
        }

        .expense-total {
            background-color: #fef2f2;
            border-top: 1px solid #fecaca;
            border-bottom: 1px solid #fecaca;
        }

        .net-profit-section {
            border-top: 2px solid #cbd5e1;
            border-bottom: 2px solid #cbd5e1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
        }

        .net-profit-label {
            font-size: 0.9rem;
            font-weight: 700;
            color: #0f172a;
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

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            background: white;
            border-radius: 6px;
            border: 2px dashed #d1d5db;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .report-container {
                width: 100%;
                border: none;
                box-shadow: none;
            }
        }

        /* Dark Mode */
        .dark .report-container {
            background: #1e293b;
            border-color: #334155;
        }

        .dark .report-title {
            color: #f1f5f9;
        }

        .dark .section-header {
            background-color: #0f172a;
            color: #3b82f6;
            border-bottom-color: #334155;
        }

        .dark .td-label {
            color: #cbd5e1;
        }

        .dark .td-value {
            color: #f8fafc;
        }

        .dark .table-row:hover {
            background-color: #334155;
        }

        .dark .revenue-total {
            background-color: #064e3b;
            border-color: #065f46;
        }

        .dark .expense-total {
            background-color: #450a0a;
            border-color: #7f1d1d;
        }
    </style>

    {{-- Filters --}}
    <div class="no-print mb-6 bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        {{ $this->getTableFiltersForm() }}
    </div>

    <div class="report-container">
        @if (isset($report))
        {{-- Header --}}
        <div class="report-header">
            <div style="flex: 0 0 100px;">
                <div class="header-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 40px; height: 40px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                </div>
            </div>

            <div style="flex: 1; text-align: center;">
                <h1 class="report-title">{{ __('lang.income_statement_report') }}</h1>
                <p class="report-subtitle">
                    {{ __('lang.from') }} {{ \Carbon\Carbon::parse($startDate)->format('d F, Y') }}
                    {{ __('lang.to') }} {{ \Carbon\Carbon::parse($endDate)->format('d F, Y') }}
                </p>
                <p class="report-subtitle" style="font-weight: 500;">
                    {{ __('lang.branch') }}: {{ $branchName ?? __('lang.all') }}
                </p>
            </div>

            <div style="flex: 0 0 100px; text-align: right;">
                <img src="{{ url('/') . '/storage/workbench.png' }}" alt="System Logo" style="height: 50px; width: auto;">
            </div>
        </div>

        {{-- Revenue Section --}}
        <div class="section-separator"></div>
        <table class="report-table">
            <tbody>
                @forelse($report['revenue']['details'] as $index => $item)
                <tr class="table-row {{ $index % 2 == 0 ? 'highlight' : 'normal' }}">
                    <td class="td-label">
                        <span class="main-label">{{ $item['account_name'] }}</span>
                        <!-- <span class="sub-label">{{ $item['account_code'] }}</span> -->
                    </td>
                    <td class="td-value">
                        
                        {{ $item['amount_formatted'] }}
                    </td>
                </tr>
                @empty
                <!-- <tr class="table-row normal">
                    <td colspan="2" class="td-label" style="text-align: center; color: #94a3b8;">{{ __('lang.no_revenue_data') }}</td>
                </tr> -->
                @endforelse
                <tr class="revenue-total">
                    <td class="td-label" style="font-weight: 700;">{{ __('lang.total_revenue') }}</td>
                    <td class="td-value" style="color: #059669;">
                        
                        {{ $report['revenue']['total_formatted'] }}
                    </td>
                </tr>

                {{-- Gap --}}
                <tr>
                    <td colspan="2" style="height: 2rem;"></td>
                </tr>

                {{-- Expenses Section --}}
                 
                <tr style="border-top: 2px solid #10b981;">
                    <td colspan="2" style="padding:0"></td>
                </tr>
                @forelse($report['expenses']['details'] as $index => $item)
                <tr class="table-row {{ $index % 2 == 0 ? 'highlight' : 'normal' }}">
                    <td class="td-label">
                        <span class="main-label">{{ $item['account_name'] }}</span>
                        <!-- <span class="sub-label">{{ $item['account_code'] }}</span> -->
                    </td>
                    <td class="td-value" style="color: #dc2626;">
                        
                        ({{ $item['amount_formatted'] }})
                    </td>
                </tr>
                @empty
                <!-- <tr class="table-row normal">
                    <td colspan="2" class="td-label" style="text-align: center; color: #94a3b8;">{{ __('lang.no_expense_data') }}</td>
                </tr> -->
                @endforelse
                <tr class="expense-total">
                    <td class="td-label" style="font-weight: 700;">{{ __('lang.total_expenses') }}</td>
                    <td class="td-value" style="color: #dc2626;">
                        
                        ({{ $report['expenses']['total_formatted'] }})
                    </td>
                </tr>
            </tbody>
        </table>

        {{-- Gross Profit --}}
        <div class="net-profit-section" style="margin-top: 2rem;">
            <div class="net-profit-label">{{ __('lang.gross_profit') }}</div>
            <div class="net-profit-amount {{ $report['gross_profit']['is_profit'] ? 'profit-positive' : 'profit-negative' }}">
                 {{ $report['gross_profit']['is_profit'] ? '' : '-' }}
                {{ $report['gross_profit']['value_formatted'] }}
            </div>
        </div>

        {{-- Gross Margin --}}
        <div class="net-profit-section">
            <div class="net-profit-label">{{ __('lang.gross_margin') }}</div>
            <div class="net-profit-amount profit-positive">
                {{ $report['gross_profit']['ratio_formatted'] }}
            </div>
        </div>

        {{-- Footer --}}
        <div style="margin-top: 3rem; border-top: 1px solid #e2e8f0; padding-top: 1rem; display: flex; justify-content: space-between;">
            <p style="font-size: 0.75rem; color: #94a3b8;">{{ __('lang.generated_on') }} {{ now()->format('d/m/Y H:i') }}</p>
            <p style="font-size: 0.75rem; color: #94a3b8;">Powered by Workbench</p>
        </div>
        @else
        <div class="empty-state">
            <p style="color: #94a3b8;">{{ __('lang.please_select_filters_to_generate_report') }}</p>
        </div>
        @endif
    </div>
</x-filament::page>