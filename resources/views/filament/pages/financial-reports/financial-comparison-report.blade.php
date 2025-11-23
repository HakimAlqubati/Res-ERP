<x-filament::page>
    <style>
        table {
            width: 100%;
            border-collapse: inherit;
            border-spacing: initial;
        }

        .stat-card {
            background-color: white;
            border-radius: 0.75rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            padding: 1.25rem;
            border: 1px solid #e5e7eb;
            text-align: center;
        }

        .dark .stat-card {
            background-color: #1f2937;
            border-color: #374151;
        }

        .stat-label {
            font-size: 0.875rem;
            /* color: #6b7280; */
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .dark .stat-label {
            /* color: #9ca3af; */
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            /* color: #111827; */
        }

        .dark .stat-value {
            color: #f3f4f6;
        }

        .trend-up {
            /* color: #059669; */
        }

        .trend-down {
            /* color: #dc2626; */
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

    @if (isset($comparisonData) && !empty($comparisonData))
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
                            <h3>{{ __('Financial Comparison Report') }}</h3>
                            <p class="text-sm text-gray-600">
                                {{ __('Period 1') }}: {{ $filters['period_one']['start_date'] }} - {{ $filters['period_one']['end_date'] }}
                                <br>
                                {{ __('Period 2') }}: {{ $filters['period_two']['start_date'] }} - {{ $filters['period_two']['end_date'] }}
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

        {{-- Comparison Table --}}
        <div class="mb-6">
            <table class="w-full text-sm text-left pretty reports table-striped border">
                <thead>
                    <tr>
                        <th class="border border-gray-300 px-4 py-2">{{ __('Metric') }}</th>
                        <th class="border border-gray-300 px-4 py-2 text-center">{{ __('Period 1') }}</th>
                        <th class="border border-gray-300 px-4 py-2 text-center">{{ __('Period 2') }}</th>
                        <th class="border border-gray-300 px-4 py-2 text-center">{{ __('Change') }}</th>
                        <th class="border border-gray-300 px-4 py-2 text-center">{{ __('Change %') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Income -->
                    <tr>
                        <td class="border border-gray-300 px-4 py-2 font-semibold">{{ __('Total Income') }}</td>
                        <td class="border border-gray-300 px-4 py-2 text-center">
                            {{ number_format($comparisonData['period_one']['statistics']['total_income'] ?? 0, 2) }}
                        </td>
                        <td class="border border-gray-300 px-4 py-2 text-center">
                            {{ number_format($comparisonData['period_two']['statistics']['total_income'] ?? 0, 2) }}
                        </td>
                        <td class="border border-gray-300 px-4 py-2 text-center font-bold {{ ($comparisonData['comparison']['income_change'] ?? 0) >= 0 ? 'trend-up' : 'trend-down' }}">
                            {{ number_format($comparisonData['comparison']['income_change'] ?? 0, 2) }}
                        </td>
                        <td class="border border-gray-300 px-4 py-2 text-center font-bold {{ ($comparisonData['comparison']['income_change_percentage'] ?? 0) >= 0 ? 'trend-up' : 'trend-down' }}">
                            {{ $comparisonData['comparison']['income_change_percentage'] ?? 0 }}%
                        </td>
                    </tr>

                    <!-- Expense -->
                    <tr>
                        <td class="border border-gray-300 px-4 py-2 font-semibold">{{ __('Total Expense') }}</td>
                        <td class="border border-gray-300 px-4 py-2 text-center">
                            {{ number_format($comparisonData['period_one']['statistics']['total_expense'] ?? 0, 2) }}
                        </td>
                        <td class="border border-gray-300 px-4 py-2 text-center">
                            {{ number_format($comparisonData['period_two']['statistics']['total_expense'] ?? 0, 2) }}
                        </td>
                        <td class="border border-gray-300 px-4 py-2 text-center font-bold {{ ($comparisonData['comparison']['expense_change'] ?? 0) <= 0 ? 'trend-up' : 'trend-down' }}">
                            {{ number_format($comparisonData['comparison']['expense_change'] ?? 0, 2) }}
                        </td>
                        <td class="border border-gray-300 px-4 py-2 text-center font-bold {{ ($comparisonData['comparison']['expense_change_percentage'] ?? 0) <= 0 ? 'trend-up' : 'trend-down' }}">
                            {{ $comparisonData['comparison']['expense_change_percentage'] ?? 0 }}%
                        </td>
                    </tr>

                    <!-- Net Balance -->
                    <tr>
                        <td class="border border-gray-300 px-4 py-2 font-semibold">{{ __('Net Balance') }}</td>
                        <td class="border border-gray-300 px-4 py-2 text-center">
                            {{ number_format($comparisonData['period_one']['statistics']['net_balance'] ?? 0, 2) }}
                        </td>
                        <td class="border border-gray-300 px-4 py-2 text-center">
                            {{ number_format($comparisonData['period_two']['statistics']['net_balance'] ?? 0, 2) }}
                        </td>
                        <td class="border border-gray-300 px-4 py-2 text-center font-bold {{ ($comparisonData['comparison']['net_balance_change'] ?? 0) >= 0 ? 'trend-up' : 'trend-down' }}">
                            {{ number_format($comparisonData['comparison']['net_balance_change'] ?? 0, 2) }}
                        </td>
                        <td class="border border-gray-300 px-4 py-2 text-center">
                            -
                        </td>
                    </tr>

                    <!-- Transaction Count -->
                    <tr>
                        <td class="border border-gray-300 px-4 py-2 font-semibold">{{ __('Total Transactions') }}</td>
                        <td class="border border-gray-300 px-4 py-2 text-center">
                            {{ $comparisonData['period_one']['statistics']['total_transactions'] ?? 0 }}
                        </td>
                        <td class="border border-gray-300 px-4 py-2 text-center">
                            {{ $comparisonData['period_two']['statistics']['total_transactions'] ?? 0 }}
                        </td>
                        <td class="border border-gray-300 px-4 py-2 text-center font-bold">
                            {{ $comparisonData['comparison']['transaction_count_change'] ?? 0 }}
                        </td>
                        <td class="border border-gray-300 px-4 py-2 text-center">
                            -
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    @else
    <div class="please_select_message_div" style="text-align: center;">
        <h1 class="please_select_message_text">{{ __('No comparison data available.') }}</h1>
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