<x-filament::page>
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
                        <th colspan="2" class="no_border_right_left text-center">
                            <h3 class="text-lg font-bold">Stock Adjustment Summary Report</h3>
                        </th>
                        <th class="text-center" colspan="2">
                            <img src="{{ asset('/storage/' . setting('company_logo')) }}" alt="Logo"
                                class="logo-left circle-image" style="display: inline-block;">
                        </th>
                    </x-filament-tables::row>
                    <x-filament-tables::row>
                        <th>Category</th>
                        <th>Adjustment Type</th>
                        <th>Product Count</th>
                        <th>Store</th>
                        <th>Total Price</th>
                    </x-filament-tables::row>
                </thead>
                <tbody>
                    @foreach ($reportData as $record)
                        @php 
                            $url = route(
                                'filament.admin.inventory-report.resources.stock-adjustment-summary-reports.view',
                                [
                                    'categoryId' => $record['category_id'],
                                    'adjustment_type' => $record['adjustment_type'],
                                    'storeId' => $record['store_id'],
                                    'fromDate' => $fromDate,
                                    'toDate' => $toDate,
                                ],
                            );
                        @endphp

                        <x-filament-tables::row x-on:click="window.open('{{ $url }}', '_blank', 'noopener,noreferrer')"
                            class="cursor-pointer hover:bg-gray-100 transition">
                            <x-filament-tables::cell class="border border-gray-300 px-4 py-2">
                                {{ $record['category'] }}
                            </x-filament-tables::cell>

                            <x-filament-tables::cell class="border border-gray-300 px-4 py-2">
                                {{ ucfirst($record['adjustment_type']) }}
                            </x-filament-tables::cell>

                            <x-filament-tables::cell class="border border-gray-300 px-4 py-2 text-center">
                                {{ $record['product_count'] }}
                            </x-filament-tables::cell>

                            <x-filament-tables::cell class="border border-gray-300 px-4 py-2">
                                {{ $record['store'] }}
                            </x-filament-tables::cell>
                            <x-filament-tables::cell class="border border-gray-300 px-4 py-2">
                                {{ $record['total_price'] }}
                            </x-filament-tables::cell>
                        </x-filament-tables::row>
                    @endforeach
                </tbody>

            </x-filament-tables::table>
        </div>
    @else
        <div class="text-center mt-10">
            <h2 class="text-gray-500 text-lg">No summary report data available.</h2>
        </div>
    @endif

    {{-- Print JS --}}
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

    <style>
        @media print {
            #printReport {
                display: none;
            }
        }
    </style>
</x-filament::page>
