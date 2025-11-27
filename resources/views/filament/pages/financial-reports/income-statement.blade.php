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
            border-collapse: collapse;
            background: white;
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

        .table-row {
            border-bottom: 1px solid #f3f4f6;
        }

        .table-row:hover {
            background-color: #f9fafb;
        }

        .row-label {
            /* padding: 0.65rem 0 0.65rem 1rem; */
            font-size: 0.875rem;
            color: #374151;
            font-weight: 500;
        }

        .row-value {
            /* padding: 0.65rem 1rem 0.65rem 0; */
            text-align: right;
            font-size: 0.875rem;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
            color: #1f2937;
        }

        .child-row {
            background-color: #fafafa;
        }

        .child-label {
            padding: 0.5rem 0 0.5rem 2.5rem;
            font-size: 0.8rem;
            color: #6b7280;
        }

        .child-value {
            padding: 0.5rem 1rem 0.5rem 0;
            text-align: right;
            font-size: 0.8rem;
            color: #6b7280;
            font-weight: 500;
        }

        .revenue-amount {
            color: #059669;
        }

        .expense-amount {
            color: #dc2626;
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
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .net-profit-amount {
            font-size: 1.5rem;
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
    </style>

    {{-- Filters Button --}}
    <div class="no-print mb-6" x-data="{ showFilters: false }">
        <button
            @click="showFilters = !showFilters"
            type="button"
            class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
            </svg>
            <span x-text="showFilters ? '{{ __('Hide') }}' : '{{ __('Show') }}'"></span>
        </button>

        {{-- Filters Modal --}}
        <div
            x-show="showFilters"
            x-cloak
            @click.self="showFilters = false"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
            style="display: none;">
            <div
                @click.stop
                class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95">
                {{-- Modal Header --}}
                <div class="flex items-center justify-between p-4 border-b">
                    <button
                        @click="showFilters = false"
                        type="button"
                        class="text-gray-400 hover:text-gray-600 focus:outline-none">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="p-6">
                    {{ $this->getTableFiltersForm() }}
                </div>


            </div>
        </div>
    </div>

    <div class="report-container">
        @if (isset($report) && !empty($report))
        {{-- Header --}}
        <div class="report-header">
            <h1 class="report-title">{{ __('Gross Profit Report') }}</h1>
            <p class="report-subtitle">
                @if($startDate && $endDate)
                {{ __('From ') }} {{ \Carbon\Carbon::parse($startDate)->format('F d, Y') }}
                {{ __('to') }} {{ \Carbon\Carbon::parse($endDate)->format('F d, Y') }}
                @else
                {{ __('All Time Records') }}
                @endif
            </p>
            @if(isset($branchName) && $branchName)
            <p class="report-subtitle" style="font-weight: 600; margin-top: 0.25rem;">
                {{ __('Branch') }}: {{ $branchName }}
            </p>
            @endif
        </div>

        {{-- Report Table --}}
        <table class="report-table">
            <tbody>
                {{-- Revenue Section --}}
                <!-- <tr>
                    <td colspan="2" class="section-header">{{ __('Revenue') }}</td>
                </tr> -->
                <tr class="table-row">
                    <td class="row-label">{{ __('Total Sales Revenue') }}</td>
                    <td class="row-value revenue-amount">
                        {{ $report['revenue']['total_formatted'] ?? number_format($report['revenue']['total'], 2) }}
                    </td>
                </tr>

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
                <span style="font-size: 1rem; margin-left: 1rem; opacity: 0.8;">({{ $report['gross_profit']['ratio_formatted'] ?? '0.00%' }})</span>
            </div>
        </div>

        {{-- Signatures --}}
        <div class="signatures">
            <div class="signature-box">
                <p class="signature-label">{{ __('Prepared By') }}</p>
            </div>
            <div class="signature-box">
                <p class="signature-label">{{ __('Approved By') }}</p>
            </div>
        </div>
        @else
        <div class="empty-state">
            <p class="empty-text">{{ __('Please select filters to generate the report.') }}</p>
        </div>
        @endif
    </div>
</x-filament::page>