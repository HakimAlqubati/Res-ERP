<x-filament::page>
    {{ $this->getTableFiltersForm() }}
    {{-- Print Button --}}
    <div class="flex justify-end mb-4">
        <button id="printReport"
            class="px-6 py-2 font-semibold rounded-md border border-blue-600 bg-blue-500 hover:bg-blue-700 transition duration-300 shadow-md">
            🖨️ Print
        </button>

        <button id="exportExcel"
            class="px-6 py-2 font-semibold rounded-md border border-green-600 bg-green-500 hover:bg-green-700 transition duration-300 shadow-md">
            📥 Export Excel
        </button>
    </div>

    @if (!empty($reportData))
        <div id="reportContent">
            <x-filament-tables::table class="w-full text-sm text-left pretty reports table-striped border">
                <thead>
                    <x-filament-tables::row class="header_report">
                        <th class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }}">
                            <img src="{{ asset('/storage/' . setting('company_logo')) }}" alt=""
                                class="logo-left circle-image">
                        </th>
                        <th colspan="6" class="no_border_right_left text-center">
                            <h3>Inventory Difference Report (Purchased vs Ordered) - Displayed in Smallest Unit</h3>
                        </th>
                        <th class="{{ app()->getLocale() == 'ar' ? 'no_border_right' : 'no_border_left' }}">
                            <img class="circle-image" src="{{ url('/') . '/storage/logo/default.png' }}" alt="">
                        </th>
                    </x-filament-tables::row>

                    <x-filament-tables::row>
                        <th>Product Code</th>
                        <th>Product Name</th>
                        <th>Unit</th>
                        <th>Purchased Qty</th>
                        <th>Ordered Qty</th>
                        <th>Qty in Stock</th>
                        <th>Unit Price</th>
                        <th>Total Price</th>
                    </x-filament-tables::row>
                </thead>

                <tbody>
                    @foreach ($reportData as $data)
                        <x-filament-tables::row>
                            <x-filament-tables::cell>{{ $data['product_code'] }}</x-filament-tables::cell>
                            <x-filament-tables::cell
                                title="{{ $data['product_id'] }}">{{ $data['product_name'] }}</x-filament-tables::cell>
                            <x-filament-tables::cell>{{ $data['unit_name'] }}</x-filament-tables::cell>
                            <x-filament-tables::cell>
                                <a href="{{ route('filament.admin.inventory-report.resources.inventory-p-report.purchase-details', ['product' => $data['product_id']]) }}"
                                    class="text-blue-600 underline hover:text-blue-800">
                                    {{ $data['purchased_qty'] }}
                                </a>
                            </x-filament-tables::cell>
                            <x-filament-tables::cell>
                                <a href="{{ route('filament.admin.inventory-report.resources.inventory-p-report.order-details', ['product' => $data['product_id']]) }}"
                                    class="text-green-600 underline hover:text-green-800">
                                    {{ $data['ordered_qty'] }}
                                </a>
                            </x-filament-tables::cell>
                            <x-filament-tables::cell>{{ $data['difference'] }}</x-filament-tables::cell>
                            <x-filament-tables::cell>{{ $data['unit_price'] }}</x-filament-tables::cell>
                            <x-filament-tables::cell>{{ $data['price'] }}</x-filament-tables::cell>
                        </x-filament-tables::row>
                    @endforeach
                </tbody>
                <tfoot>
                    <x-filament-tables::row class="font-bold bg-gray-100">
                        <x-filament-tables::cell colspan="7" class="text-right">Total:</x-filament-tables::cell>
                        
                        <x-filament-tables::cell>
                            {{ number_format(array_sum(array_column($reportData, 'price')), 2) }}
                        </x-filament-tables::cell>
                    </x-filament-tables::row>
                </tfoot>

            </x-filament-tables::table>
        </div>
    @else
        <div class="please_select_message_div text-center">
            <h1 class="please_select_message_text">No data available.</h1>
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
            location.reload(); // Restore after print
        });
    </script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <script>
        document.getElementById("exportExcel").addEventListener("click", function() {
            const table = document.querySelector("#reportContent table");
            const wb = XLSX.utils.table_to_book(table, {
                sheet: "Inventory Report"
            });
            XLSX.writeFile(wb, "inventory_difference_report.xlsx");
        });

        document.getElementById("printReport").addEventListener("click", function() {
            const originalContent = document.body.innerHTML;
            const printContent = document.getElementById("reportContent").innerHTML;

            document.body.innerHTML = printContent;
            window.print();
            document.body.innerHTML = originalContent;
            location.reload();
        });
    </script>

    {{-- CSS to Hide Print Button --}}
    <style>
        @media print {
            #printReport {
                display: none;
            }
        }
    </style>
</x-filament::page>
