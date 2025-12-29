<x-filament::page>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        .comparison-container {
            max-width: 100%;
            margin: 0 auto;
            padding: 1.5rem;
            background: white;
            border: 2px solid #cbd5e1;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }

        .comparison-header {
            text-align: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e5e7eb;
        }

        .comparison-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 0.5rem 0;
        }

        .comparison-subtitle {
            font-size: 0.85rem;
            color: #64748b;
        }

        .comparison-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.875rem;
        }

        .comparison-table th {
            background: linear-gradient(135deg, #047857 0%, #10b981 100%);
            color: white;
            padding: 0.75rem 1rem;
            text-align: center;
            font-weight: 600;
            border: 1px solid #047857;
            white-space: nowrap;
        }

        .comparison-table th:first-child {
            text-align: left;
            border-top-left-radius: 6px;
        }

        .comparison-table th:last-child {
            border-top-right-radius: 6px;
        }

        .comparison-table th.total-header {
            background: linear-gradient(135deg, #0f172a 0%, #334155 100%);
            border-color: #0f172a;
        }

        .comparison-table td {
            padding: 0.65rem 1rem;
            border: 1px solid #e5e7eb;
            text-align: center;
        }

        .comparison-table td:first-child {
            text-align: left;
            font-weight: 600;
            color: #1f2937;
            background: #f8fafc;
        }

        .comparison-table tr:nth-child(even) td {
            background-color: #f9fafb;
        }

        .comparison-table tr:nth-child(even) td:first-child {
            background-color: #f1f5f9;
        }

        .comparison-table tr:hover td {
            background-color: #eff6ff !important;
        }

        .comparison-table .total-column {
            background: linear-gradient(90deg, transparent, #f0fdf4 100%) !important;
            font-weight: 700;
            color: #000000;
        }

        .comparison-table .gross-profit-row td {
            background: #f0fdf4 !important;
            font-weight: 700;
            border-top: 2px solid #10b981;
            border-bottom: 2px solid #10b981;
        }

        .comparison-table .gross-profit-row td.positive {
            color: #059669;
        }

        .comparison-table .gross-profit-row td.negative {
            color: #dc2626;
        }

        .comparison-table .net-profit-row td {
            background: #fef3c7 !important;
            font-weight: 700;
            border-top: 2px solid #f59e0b;
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

        .info-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 9999px;
            font-size: 0.75rem;
            color: #1e40af;
            margin-top: 1rem;
        }

        @media print {
            .no-print {
                display: none !important;
            }
        }

        @media (max-width: 768px) {
            .comparison-container {
                padding: 1rem;
            }

            .comparison-table {
                font-size: 0.75rem;
            }

            .comparison-table th,
            .comparison-table td {
                padding: 0.5rem;
            }
        }

        /* Dark Mode Styles */
        .dark .comparison-container {
            background: #1e293b;
            border-color: #334155;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .dark .comparison-header {
            border-bottom-color: #475569;
        }

        .dark .comparison-title {
            color: #f1f5f9;
        }

        .dark .comparison-subtitle {
            color: #94a3b8;
        }

        .dark .comparison-table td {
            border-color: #475569;
            color: #e2e8f0;
            background-color: #1e293b;
        }

        .dark .comparison-table td:first-child {
            color: #f1f5f9;
            background: #0f172a;
        }

        .dark .comparison-table tr:nth-child(even) td {
            background-color: #334155;
        }

        .dark .comparison-table tr:nth-child(even) td:first-child {
            background-color: #1e293b;
        }

        .dark .comparison-table tr:hover td {
            background-color: #3b4f6b !important;
        }

        .dark .comparison-table .total-column {
            background: linear-gradient(90deg, transparent, #064e3b 100%) !important;
            color: #10b981;
        }

        .dark .comparison-table .gross-profit-row td {
            background: #064e3b !important;
            border-top-color: #10b981;
            border-bottom-color: #10b981;
        }

        .dark .comparison-table .gross-profit-row td.positive {
            color: #34d399;
        }

        .dark .comparison-table .gross-profit-row td.negative {
            color: #f87171;
        }

        .dark .comparison-table .net-profit-row td {
            background: #422006 !important;
            border-top-color: #f59e0b;
        }

        .dark .empty-state {
            background: #1e293b;
            border-color: #475569;
        }

        .dark .empty-text {
            color: #94a3b8;
        }

        .dark .info-badge {
            background: #1e3a5f;
            border-color: #3b82f6;
            color: #93c5fd;
        }

        .dark .please_select_message_text {
            color: #94a3b8;
        }
    </style>

    {{-- Filters Form --}}
    <div class="no-print mb-6 bg-white p-4 rounded-lg shadow-sm border border-gray-200">
        {{ $this->getTableFiltersForm() }}
    </div>

    @if(isset($comparisonData) && !empty($comparisonData['headers']))
    <div class="comparison-container">
        {{-- Header --}}
        <div class="comparison-header" style="display: flex; justify-content: space-between; align-items: flex-start; text-align: left; border-bottom: none; margin-bottom: 1rem; padding-bottom: 0;">
            {{-- Company Logo (Left) --}}
            <div style="flex: 0 0 80px;">
                <img src="{{ asset('/storage/' . setting('company_logo')) }}" alt="Company Logo"
                    style="height: 60px; width: auto; object-fit: contain; border-radius: 0%;">
            </div>

            {{-- Title (Center) --}}
            <div style="flex: 1; text-align: center;">
                <h1 class="comparison-title">{{ __('Gross Profit Report') }}</h1>
                <p class="comparison-subtitle">
                    @if($startDate && $endDate)
                    {{ __('From ') }} {{ \Carbon\Carbon::parse($startDate)->format('d F, Y') }}
                    {{ __('to') }} {{ \Carbon\Carbon::parse($endDate)->format('d F, Y') }}
                    @else
                    {{ __('All Time Records') }}
                    @endif
                </p>
                <p class="comparison-subtitle" style="font-weight: 600; margin-top: 0.25rem;">
                    {{ __('Comparing') }} {{ $comparisonData['meta']['branch_count'] }} {{ __('Branches') }}
                </p>
            </div>

            {{-- System Logo (Right) --}}
            <div style="flex: 0 0 80px; text-align: right;">
                <img src="{{ url('/') . '/storage/workbench.png' }}" alt="System Logo"
                    style="height: 40px; width: auto; object-fit: contain;">
            </div>
        </div>

        {{-- Comparison Table --}}
        <table class="comparison-table">
            <thead>
                <tr>
                    <th>{{ __('Item') }}</th>
                    @foreach($comparisonData['headers'] as $header)
                    <th class="{{ $header['is_total'] ?? false ? 'total-header' : '' }}">
                        {{ $header['name'] }}
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($comparisonData['rows'] as $rowKey => $row)
                <tr class="{{ in_array($rowKey, ['gross_profit', 'gross_margin']) ? 'gross-profit-row' : '' }} {{ $rowKey === 'net_profit' ? 'net-profit-row' : '' }}">
                    <td>{{ $row['label'] }}</td>
                    @foreach($comparisonData['headers'] as $header)
                    @php
                    $value = $row['values'][$header['id']] ?? '-';
                    $isTotal = $header['is_total'] ?? false;
                    @endphp
                    <td class="{{ $isTotal ? 'total-column' : '' }}">
                        {{ $value }}
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Footer --}}
        <div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
            {{-- Formula --}}
            <p style="font-size: 0.7rem; color: #6b7280; margin: 0 0 0.5rem 0; font-weight: 500; text-align: center;">
                Gross Profit = Sales + Closing Stock – Transfers – Direct Purchases
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
        <div class="please_select_message_div" style="text-align: center;">
            <h1 class="please_select_message_text">{{
                __('Please select branches to compare from the filters above.') }}</h1>
        </div>
    </div>
    @endif
</x-filament::page>