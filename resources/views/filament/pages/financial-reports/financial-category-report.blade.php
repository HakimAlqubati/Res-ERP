<x-filament::page>
    <style>
        table {
            width: 100%;
            border-collapse: inherit;
            border-spacing: initial;
        }

        .fi-tabs {
            /* display: none !important; */
        }

        .stat-card {
            background-color: white;
            border-radius: 0.75rem;
            box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.1);
            padding: 1.25rem;
            border: 1px solid #e5e7eb;
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
            min-height: 10px;
            aspect-ratio: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.15);
        }

        .dark .stat-card {
            background-color: #1f2937;
            border-color: #374151;
        }

        .stat-label {
            font-size: 0.75rem;
            color: #6b7280;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }

        .dark .stat-label {
            color: #9ca3af;
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            /* line-height: 1.2; */
        }

        .stat-count {
            font-size: 0.7rem;
            color: #9ca3af;
            font-weight: 500;
        }

        .dark .stat-count {
            color: #6b7280;
        }

        .income-color {
            color: #059669;
        }

        .dark .income-color {
            color: #34d399;
        }

        .expense-color {
            color: #dc2626;
        }

        .dark .expense-color {
            color: #f87171;
        }

        .balance-color {
            color: #2563eb;
        }

        .dark .balance-color {
            color: #60a5fa;
        }
    </style>

    {{-- Print Button --}}
    <div class="flex justify-end gap-3 mb-4">
        <button id="printReport"
            class="px-6 py-2 font-semibold rounded-md border border-blue-600 bg-blue-500 hover:bg-blue-700 transition duration-300 shadow-md">
            🖨️ {{ __('Print') }}
        </button>
    </div>

    {{-- Filters --}}
    {{ $this->getTableFiltersForm() }}

    @if (isset($reportData) && !empty($reportData))
        <div id="reportContent">
            {{-- Report Header --}}
            <div class="mb-6">
                <table class="w-full text-sm text-left pretty reports table-striped border">
                    <thead class="fixed-header">
                        <tr class="header_report">
                            <th class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }}">
                                <div style="width: 100%;"></div>
                            </th>
                            <th colspan="2" class="no_border_right_left text-center">
                                <h3>{{ __('Financial Category Report') }}</h3>
                                <p class="text-sm text-gray-600">
                                    {{ __('From') }}: {{ $filters['start_date'] ?? 'N/A' }} |
                                    {{ __('To') }}: {{ $filters['end_date'] ?? 'N/A' }}
                                </p>
                            </th>
                            <th colspan="3"
                                class="{{ app()->getLocale() == 'ar' ? 'no_border_right' : 'no_border_left' }}"
                                style="text-align: center;">
                                <img style="display: inline-block;"
                                    src="{{ asset('/storage/' . setting('company_logo') . '') }}" alt=""
                                    class="logo-left circle-image">
                            </th>
                        </tr>
                    </thead>
                </table>
            </div>

            {{-- Statistics Summary --}}
            @if (isset($reportData['statistics']) && 1==1)
                <table class="w-full text-sm text-left pretty reports table-striped border mb-6">
                    <thead>
                        <tr>
                            <th class="border border-gray-300 px-4 py-2 text-center">{{ __('Total Income') }}</th>
                            <th class="border border-gray-300 px-4 py-2 text-center">{{ __('Total Expense') }}</th>
                            <th class="border border-gray-300 px-4 py-2 text-center">{{ __('Net Balance') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border border-gray-300 px-4 py-2 text-center">
                                <div class="text-2xl font-bold income-color">
                                    {{ number_format($reportData['statistics']['totals']['income'] ?? 0, 2) }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $reportData['statistics']['transaction_counts']['income'] ?? 0 }} {{ __('transactions') }}
                                </div>
                            </td>
                            <td class="border border-gray-300 px-4 py-2 text-center">
                                <div class="text-2xl font-bold expense-color">
                                    {{ number_format($reportData['statistics']['totals']['expense'] ?? 0, 2) }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $reportData['statistics']['transaction_counts']['expense'] ?? 0 }} {{ __('transactions') }}
                                </div>
                            </td>
                            <td class="border border-gray-300 px-4 py-2 text-center">
                                <div class="text-2xl font-bold balance-color">
                                    {{ number_format($reportData['statistics']['totals']['net_balance'] ?? 0, 2) }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $reportData['statistics']['transaction_counts']['total'] ?? 0 }} {{ __('total transactions') }}
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            @endif

            {{-- Category Summaries Table --}}
            @if (isset($reportData['category_summaries']) && count($reportData['category_summaries']) > 0)
                <div class="mb-6">
                    <h4 class="text-lg font-semibold mb-3">{{ __('Category Breakdown') }}</h4>
                    <table class="w-full text-sm text-left pretty reports table-striped border">
                        <thead>
                            <tr>
                                <th class="border border-gray-300 px-4 py-2">{{ __('Category Name') }}</th>
                                <th class="border border-gray-300 px-4 py-2">{{ __('Type') }}</th>
                                <th class="border border-gray-300 px-4 py-2 text-right">{{ __('Total Amount') }}</th>
                                <th class="border border-gray-300 px-4 py-2 text-center">{{ __('Transactions') }}</th>
                                <th class="border border-gray-300 px-4 py-2 text-right">{{ __('Average') }}</th>
                                <th class="border border-gray-300 px-4 py-2 text-right">{{ __('Min') }}</th>
                                <th class="border border-gray-300 px-4 py-2 text-right">{{ __('Max') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reportData['category_summaries'] as $category)
                                <tr>
                                    <td class="border border-gray-300 px-4 py-2">
                                        <strong>{{ $category['category_name'] }}</strong>
                                    </td>
                                    <td class="border border-gray-300 px-4 py-2">
                                        <span
                                            class="px-2 py-1 rounded text-xs {{ $category['category_type'] == 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ __(ucfirst($category['category_type'])) }}
                                        </span>
                                    </td>
                                    <td class="border border-gray-300 px-4 py-2 text-right font-bold">
                                        {{ number_format($category['total_amount'], 2) }}
                                    </td>
                                    <td class="border border-gray-300 px-4 py-2 text-center">
                                        {{ $category['transaction_count'] }}
                                    </td>
                                    <td class="border border-gray-300 px-4 py-2 text-right">
                                        {{ number_format($category['average_amount'], 2) }}
                                    </td>
                                    <td class="border border-gray-300 px-4 py-2 text-right text-sm text-gray-600">
                                        {{ number_format($category['min_amount'] ?? 0, 2) }}
                                    </td>
                                    <td class="border border-gray-300 px-4 py-2 text-right text-sm text-gray-600">
                                        {{ number_format($category['max_amount'] ?? 0, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="please_select_message_div text-center">
                    <h1 class="please_select_message_text">{{ __('No category data available for the selected filters.') }}</h1>
                </div>
            @endif
        </div>
    @else
        <div class="please_select_message_div" style="text-align: center;">
            <h1 class="please_select_message_text">{{ __('Please select filters to generate the report.') }}</h1>
        </div>
    @endif

    {{-- JavaScript to Handle Printing --}}
    <script>
        document.getElementById("printReport").addEventListener("click", function() {
            const originalContent = document.body.innerHTML;
            const printContent = document.getElementById("reportContent").innerHTML;

            document.body.innerHTML = printContent;
            window.print();
            document.body.innerHTML = originalContent;
            location.reload(); // Reload to restore the page
        });
    </script>

    {{-- CSS to Hide Button in Print Mode --}}
    <style>
        @media print {
            #printReport {
                display: none;
            }
        }
    </style>

</x-filament::page>
