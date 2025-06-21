<x-filament::page>
    {{-- Print Button --}}
    <style>
        table {
            width: 100%;
            border-collapse: inherit;
            border-spacing: initial;
        }

        table.out-table tbody tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        table.out-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }

        table.out-table th,
        table.out-table td {
            padding: 6px;
            border: 1px solid #ccc;
        }

        .teal-striped-table thead {
            background-color: #035c55 !important;
            color: white;
        }

        .teal-striped-table tbody tr:nth-child(odd) {
            background-color: #ffffff;
        }

        .teal-striped-table tbody tr:nth-child(even) {
            background-color: #d1e9e6;
            /* Teal-tinted light background */
        }

        .teal-striped-table th,
        .teal-striped-table td {
            border: 1px solid #ccc;
            padding: 8px;
            color: #000;
            /* لتوحيد لون النص */
        }

        .teal-striped-table tbody tr:nth-child(even) {
            background-color: #1f685f;
            color: #ffffff;
        }
        .white_clr{
            color: #ffffff !important;
        }
    </style>

    <div class="flex justify-end mb-4">
        <button id="printReport"
            class="px-6 py-2 font-semibold rounded-md border border-blue-600 bg-blue-500 hover:bg-blue-700 transition duration-300 shadow-md">
            🖨️ Print
        </button>
    </div>

    {{ $this->getTableFiltersForm() }}

    @if (count($reportData) > 0)
        <div id="reportContent">
            <x-filament-tables::table class="w-full text-sm text-left pretty reports table-striped border">
                <thead class="fixed-header">
                    <x-filament-tables::row class="header_report">
                        <th class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }}"></th>
                        <th colspan="5" class="no_border_right_left text-center">
                            <h3 class="text-lg font-bold">Inbound → Outflow Report</h3>
                        </th>
                        <th colspan="2"
                            class="{{ app()->getLocale() == 'ar' ? 'no_border_right' : 'no_border_left' }}"
                            style="text-align: center;">
                            <img src="{{ asset('/storage/' . setting('company_logo')) }}" alt="Logo"
                                class="logo-left circle-image" style="display: inline-block;">
                        </th>
                    </x-filament-tables::row>
                    <x-filament-tables::row>
                        <th>IN ID</th>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Unit</th>
                        <th>Qty Per Pack</th>
                        <th>Price</th>
                        <th>Date</th>
                        <th>OUT Transactions</th>
                    </x-filament-tables::row>
                </thead>
                <tbody>
                    @foreach ($reportData as $record)
                        <x-filament-tables::row>
                            <x-filament-tables::cell class="border border-gray-300 px-4 py-2">
                                {{ $record['in_transaction_id'] }}
                            </x-filament-tables::cell>
                            <x-filament-tables::cell class="border border-gray-300 px-4 py-2">
                                {{ $record['product_name'] }}
                            </x-filament-tables::cell>
                            <x-filament-tables::cell class="border border-gray-300 px-4 py-2">
                                {{ $record['quantity'] }}
                            </x-filament-tables::cell>
                            <x-filament-tables::cell class="border border-gray-300 px-4 py-2">
                                {{ $record['unit_name'] }}
                            </x-filament-tables::cell>
                            <x-filament-tables::cell class="border border-gray-300 px-4 py-2">
                                {{ $record['package_size'] }}
                            </x-filament-tables::cell>
                            <x-filament-tables::cell class="border border-gray-300 px-4 py-2">
                                {{ $record['price'] }}
                            </x-filament-tables::cell>
                            <x-filament-tables::cell class="border border-gray-300 px-4 py-2">
                                {{ $record['transaction_date'] }}
                            </x-filament-tables::cell>
                            <x-filament-tables::cell class="border border-gray-300 px-4 py-2">
                                <table class="teal-striped-table w-full text-sm border mt-2">

                                    <thead>
                                        <tr>
                                            <th class="white_clr">Quantity</th>
                                            <th class="white_clr">Unit</th>
                                            <th class="white_clr">Unit Price</th>
                                            <th class="white_clr">Date</th>
                                            <th class="white_clr">Source</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($record['outflows'] as $out)
                                            <tr>
                                                <td>{{ $out['quantity'] }}</td>
                                                <td>{{ $out['unit'] }}</td>
                                                <td>{{ $out['price'] }}</td>
                                                <td>{{ $out['transaction_date'] }}</td>
                                                <td>
                                                    {{ class_basename($out['transactionable_type']) }}
                                                    #{{ $out['transactionable_id'] }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>

                            </x-filament-tables::cell>
                        </x-filament-tables::row>
                    @endforeach
                </tbody>
            </x-filament-tables::table>
        </div>
    @else
        <div class="text-center mt-10">
            <h2 class="text-gray-500 text-lg">No inbound transactions found for selected ID.</h2>
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
            location.reload();
        });
    </script>

    {{-- Hide Print Button on Print --}}
    <style>
        @media print {
            #printReport {
                display: none;
            }
        }
    </style>
</x-filament::page>
