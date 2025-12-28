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
            color: #047857;
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
    </style>

    {{-- Filters Form --}}
    <div class="no-print mb-6 bg-white p-4 rounded-lg shadow-sm border border-gray-200">
        {{ $this->getTableFiltersForm() }}
    </div>

    <div class="comparison-container">
        @if(isset($comparisonData) && !empty($comparisonData['headers']))
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
        <div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #e5e7eb; text-align: center;">
            <p style="font-size: 0.7rem; color: #6b7280; margin: 0;">
                {{ __('Generated on') }} {{ \Carbon\Carbon::now()->format('d F, Y - h:i A') }}
            </p>
        </div>
        @else
        <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="empty-text">{{ __('Please select branches to compare from the filters above.') }}</p>
        </div>
        @endif
    </div>
</x-filament::page>