<x-filament::page>
    {{-- Print Button --}}
    <style>
        table {
            width: 100%;
            border-collapse: inherit;
            border-spacing: initial;
        }
    </style>

    <div class="flex justify-end mb-4">
        <button id="printReport"
            class="px-6 py-2 font-semibold rounded-md border border-blue-600 bg-blue-500 hover:bg-blue-700 transition duration-300 shadow-md">
            🖨️ Print
        </button>
    </div>

    {{ $this->getTableFiltersForm() }}

    @if (isset($storeId) || $storeId != null)
        @if (count($reportData) > 0)
            <div id="reportContent">
                <x-filament-tables::table class="w-full text-sm text-left pretty reports table-striped border">
                    <thead class="fixed-header">
                        <x-filament-tables::row class="header_report">
                            <th class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }}"></th>
                            <th colspan="3" class="no_border_right_left text-center">
                                <h3 class="text-lg font-bold">Inventory Transaction Report</h3>
                            </th>
                            <th colspan="3"
                                class="{{ app()->getLocale() == 'ar' ? 'no_border_right' : 'no_border_left' }}"
                                style="text-align: center;">
                                <img src="{{ asset('/storage/' . setting('company_logo')) }}" alt="Logo"
                                    class="logo-left circle-image" style="display: inline-block;">
                            </th>
                        </x-filament-tables::row>
                        <x-filament-tables::row>

                            <th>Source Type</th>
                            <th>Source ID</th>
                            <th>Date</th>

                            <th>Unit</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Total Value</th>
                        </x-filament-tables::row>
                    </thead>
                    <tbody>
                        @foreach ($reportData as $batch)
                            @foreach ($batch['units_breakdown'] as $unit)
                                <x-filament-tables::row>

                                    <x-filament-tables::cell class="border border-gray-300 px-4 py-2">
                                        {{ class_basename($batch['transactionable_type']) }}
                                    </x-filament-tables::cell>
                                    <x-filament-tables::cell class="border border-gray-300 px-4 py-2">
                                        {{ $batch['transactionable_id'] }}
                                    </x-filament-tables::cell>
                                    <x-filament-tables::cell class="border border-gray-300 px-4 py-2">
                                        {{ $batch['transaction_date'] }}
                                    </x-filament-tables::cell>


                                    <x-filament-tables::cell class="border border-gray-300 px-4 py-2">
                                        {{ $unit['unit_name'] }}
                                    </x-filament-tables::cell>
                                    <x-filament-tables::cell class="border border-gray-300 px-4 py-2">
                                        {{ $unit['remaining_quantity'] }}
                                    </x-filament-tables::cell>
                                    <x-filament-tables::cell class="border border-gray-300 px-4 py-2">
                                        {{ $unit['price'] }}
                                    </x-filament-tables::cell>
                                    <x-filament-tables::cell class="border border-gray-300 px-4 py-2 font-bold">
                                        {{ $unit['total_value'] }}
                                    </x-filament-tables::cell>
                                </x-filament-tables::row>
                            @endforeach
                        @endforeach
                        @if ($onlySmallestUnit)
                            <x-filament-tables::row>
                                <x-filament-tables::cell colspan="6" class="border border-gray-300 px-4 py-2">
                                    Total
                                </x-filament-tables::cell>
                                <x-filament-tables::cell class="border border-gray-300 px-4 py-2">
                                    {{ $finalTotalValue }}
                                </x-filament-tables::cell>
                            </x-filament-tables::row>
                        @endif
                    </tbody>
                </x-filament-tables::table>
            </div>
        @else
            <div class="text-center mt-10">
                <h2 class="text-gray-500 text-lg">No inventory transaction data available.</h2>
            </div>
        @endif
    @else
        <div class="text-center mt-10">
            <h2 class="text-gray-500 text-lg">{{ __('lang.please_select_store') }}</h2>
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
