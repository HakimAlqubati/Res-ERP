<x-filament::page>
    {{-- Print Button --}}
    <div class="flex justify-end mb-4">
        <button id="printReport"
            class="px-6 py-2 font-semibold rounded-md border border-blue-600 bg-blue-500 hover:bg-blue-700 transition duration-300 shadow-md">
            🖨️ Print
        </button>
    </div>

    {{ $this->getTableFiltersForm() }}

    @if (!empty($reportData))
        <div id="reportContent">
            <x-filament-tables::table class="w-full text-sm text-left pretty reports table-striped border">
                <thead>
                    <x-filament-tables::row class="header_report">
                        <th></th>
                        <th colspan="3" class="text-center">
                            <h3>Inventory Report</h3>
                        </th>
                        <th colspan="2" style="text-align: center;">
                            <img class="circle-image" src="{{ url('/') . '/storage/logo/default.png' }}" alt="">
                        </th>
                    </x-filament-tables::row>
                    <x-filament-tables::row>
                        <th>Product Name</th>
                        <th>Unit ID</th>
                        <th>Unit Name</th>
                        <th>Package Size</th>
                        <th>Quantity in Stock</th>
                    </x-filament-tables::row>
                </thead>
                <tbody>
                    @foreach ($reportData as $productReport)
                        @foreach ($productReport as $data)
                            <x-filament-tables::row>
                                <x-filament-tables::cell class="border border-gray-300 px-4 py-2">
                                    <strong>{{ $data['product_name'] }}</strong>
                                </x-filament-tables::cell>
                                <x-filament-tables::cell class="border border-gray-300 px-4 py-2">
                                    {{ $data['unit_id'] }}
                                </x-filament-tables::cell>
                                <x-filament-tables::cell class="border border-gray-300 px-4 py-2">
                                    {{ $data['unit_name'] }}
                                </x-filament-tables::cell>
                                <x-filament-tables::cell class="border border-gray-300 px-4 py-2">
                                    {{ $data['package_size'] }}
                                </x-filament-tables::cell>
                                <x-filament-tables::cell class="border border-gray-300 px-4 py-2 font-bold">
                                    {{ $data['remaining_qty'] }}
                                </x-filament-tables::cell>
                            </x-filament-tables::row>
                        @endforeach
                    @endforeach
                </tbody>
            </x-filament-tables::table>
        </div>

        {{-- Pagination Controls --}}
        @if ($reportData instanceof \Illuminate\Pagination\LengthAwarePaginator)
            <div class="mt-4">
                {{ $pagination->links() }}
            </div>
        @endif
    @else
        <div class="please_select_message_div text-center">
            <h1 class="please_select_message_text">No inventory data available.</h1>
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
