<x-filament::page>
    <style>
        table {
            width: 100%;
            border-collapse: inherit;
            border-spacing: initial;
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

    @if (isset($transactions) && count($transactions) > 0)
    <div id="reportContent">
        {{-- Report Header --}}
        <div class="mb-6">
            <table class="w-full text-sm text-left pretty reports table-striped border">
                <thead class="fixed-header">
                    <tr class="header_report">
                        <th class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }}">
                            <div style="width: 100%;"></div>
                        </th>
                        <th colspan="1" class="no_border_right_left text-center">
                            <h3>{{ __('Financial Details Report') }}</h3>
                            <p class="text-sm text-gray-600">
                                {{ __('From') }}: {{ $filters['start_date'] ?? 'N/A' }} |
                                {{ __('To') }}: {{ $filters['end_date'] ?? 'N/A' }}
                            </p>
                        </th>
                        <th colspan="2"
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

        {{-- Totals Summary --}}
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
                        <div class="text-xl font-bold income-color">
                            {{ number_format($totals['income'] ?? 0, 2) }}
                        </div>
                    </td>
                    <td class="border border-gray-300 px-4 py-2 text-center">
                        <div class="text-xl font-bold expense-color">
                            {{ number_format($totals['expense'] ?? 0, 2) }}
                        </div>
                    </td>
                    <td class="border border-gray-300 px-4 py-2 text-center">
                        <div class="text-xl font-bold balance-color">
                            {{ number_format($totals['net_balance'] ?? 0, 2) }}
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        {{-- Transactions Table --}}
        <div class="mb-6">
            <!-- <h4 class="text-lg font-semibold mb-3">{{ __('Transactions List') }}</h4> -->
            <table class="w-full text-sm text-left pretty reports table-striped border">
                <thead>
                    <tr>
                        <th class="border border-gray-300 px-4 py-2">{{ __('Date') }}</th>
                        <th class="border border-gray-300 px-4 py-2">{{ __('Branch') }}</th>
                        <th class="border border-gray-300 px-4 py-2">{{ __('Category') }}</th>
                        <th class="border border-gray-300 px-4 py-2">{{ __('Description') }}</th>
                        <th class="border border-gray-300 px-4 py-2">{{ __('Type') }}</th>
                        <th class="border border-gray-300 px-4 py-2 text-right">{{ __('Amount') }}</th>
                        <!-- <th class="border border-gray-300 px-4 py-2 text-center">{{ __('Status') }}</th> -->
                    </tr>
                </thead>
                <tbody>
                    @foreach ($transactions as $transaction)
                    <tr>
                        <td class="border border-gray-300 px-4 py-2">
                            {{ $transaction->transaction_date ? $transaction->transaction_date->format('Y-m-d') : 'N/A' }}
                        </td>
                        <td class="border border-gray-300 px-4 py-2">
                            {{ $transaction->branch->name ?? 'N/A' }}
                        </td>
                        <td class="border border-gray-300 px-4 py-2">
                            {{ $transaction->category->name ?? 'N/A' }}
                        </td>
                        <td class="border border-gray-300 px-4 py-2">
                            {{ Str::limit($transaction->description, 50) }}
                        </td>
                        <td class="border border-gray-300 px-4 py-2">
                            <span
                                class="px-2 py-1 rounded text-xs {{ $transaction->type == 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ __(ucfirst($transaction->type)) }}
                            </span>
                        </td>
                        <td class="border border-gray-300 px-4 py-2 text-right font-bold">
                            {{ number_format($transaction->amount, 2) }}
                        </td>
                        <!-- <td class="border border-gray-300 px-4 py-2 text-center">
                            <span
                                class="px-2 py-1 rounded text-xs 
                                        {{ $transaction->status == 'paid' ? 'bg-green-100 text-green-800' : ($transaction->status == 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                {{ __(ucfirst($transaction->status)) }}
                            </span>
                        </td> -->
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @else
    <div class="please_select_message_div" style="text-align: center;">
        <h1 class="please_select_message_text">{{ __('No transactions found for the selected filters.') }}</h1>
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