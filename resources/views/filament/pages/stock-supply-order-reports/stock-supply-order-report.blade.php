<x-filament::page>
    {{-- Print Button --}}
    <div class="flex justify-end mb-4">
        <button id="printReport"
            class="px-6 py-2 font-semibold rounded-md border border-blue-600 bg-blue-500 hover:bg-blue-700 transition duration-300 shadow-md">
            🖨️ Print
        </button>
    </div>

    {{ $this->getTableFiltersForm() }}

    @if (!empty($store))
        @if ($store && !empty($reportData))

            <x-filament-tables::table class="w-full text-sm text-left border reports table-striped" id="report-table">
                <thead class="fixed-header" style="top:64px;">
                    <x-filament-tables::row class="header_report">
                        <th class="no_border_right text-center">
                            <img src="{{ asset('/storage/' . setting('company_logo')) }}" alt="Company Logo"
                                class="logo-img-report circle-image">
                        </th>
                        <th colspan="2" class="no_border_right_left text-left">
                            <h3>Aggregated Stock Supply Report for ({{ $store }})</h3>
                            <h5>
                                Between {{ $startDate . ' & ' . $endDate }}
                            </h5>
                        </th>
                        <th class="no_border_left text-center">
                            <img class="circle-image" src="{{ asset('storage/workbench.png') }}" alt="">
                        </th>
                    </x-filament-tables::row>
                    <x-filament-tables::row class="bg-blue-50 text-xs text-gray-700">
                        <th class="px-4 py-2">Product</th>
                        <th class="px-4 py-2">Product Code</th>
                        <th class="px-4 py-2">Unit</th>
                        <th class="px-4 py-2">Total Quantity</th>
                    </x-filament-tables::row>
                </thead>
                <tbody>
                    @foreach ($reportData as $row)
                        <x-filament-tables::row>
                            <x-filament-tables::cell
                                class="border px-4 py-1 text-center">{{ $row['product_name'] }}</x-filament-tables::cell>
                            <x-filament-tables::cell
                                class="border px-4 py-1 text-center">{{ $row['product_code'] }}</x-filament-tables::cell>
                            <x-filament-tables::cell
                                class="border px-4 py-1 text-center">{{ $row['unit_name'] }}</x-filament-tables::cell>
                            <x-filament-tables::cell
                                class="border px-4 py-1 text-center">{{ $row['total_quantity'] }}</x-filament-tables::cell>
                        </x-filament-tables::row>
                    @endforeach
                </tbody>
            </x-filament-tables::table>
        @else
            <div class="please_select_message_div text-center">
                <h1 class="please_select_message_text">No supply data found for store ({{ $store }}).</h1>
            </div>
        @endif
    @else
        <div class="please_select_message_div text-center">
            <h1 class="please_select_message_text">Please select a store.</h1>
        </div>
    @endif

    {{-- Print Script --}}
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
