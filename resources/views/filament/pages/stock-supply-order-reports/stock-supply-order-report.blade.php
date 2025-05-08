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
            <div id="reportContent">
                <x-filament-tables::table class="w-full text-sm text-left border reports table-striped" id="report-table">
                    <thead class="fixed-header" style="top:64px;">
                        <x-filament-tables::row class="header_report">
                            <th class="no_border_right text-center">

                                <img src="{{ asset('/storage/' . setting('company_logo')) }}" alt="Company Logo"
                                    class="logo-img-report circle-image">
                            </th>
                            <th colspan="2" class="no_border_right_left text-left">
                                <h3>Stock Supply Orders for ({{ $store }})</h3>
                                <h5>
                                    Between {{$startDate .'   &   '. $endDate}}
                                </h5>
                            </th>
                            <th class="no_border_left text-center">
                                <img class="circle-image" src="{{ asset('storage/workbench.png') }}" alt="">
                            </th>
                        </x-filament-tables::row>
                    </thead>
                    <tbody>
                        @foreach ($reportData as $order)
                            {{-- Visual Separator --}}


                            {{-- Basic Order Info --}}
                            <x-filament-tables::row class="bg-gray-100 font-bold">
                                <x-filament-tables::cell class="border px-4 py-2">Order ID:
                                    {{ $order['order_id'] }}</x-filament-tables::cell>
                                <x-filament-tables::cell colspan="3" class="border px-4 py-2">Order Date:
                                    {{ $order['order_date'] }}</x-filament-tables::cell>
                            </x-filament-tables::row>

                            {{-- Detail Header --}}
                            <x-filament-tables::row class="bg-blue-50 text-xs text-gray-700">

                                <th class="px-4 py-1">Product</th>
                                <th class="px-4 py-1">Product Code</th>
                                <th class="px-4 py-1">Unit</th>
                                <th class="px-4 py-1">Quantity</th>
                            </x-filament-tables::row>

                            {{-- Details Rows --}}
                            @foreach ($order['details'] as $index => $detail)
                                <x-filament-tables::row>

                                    <x-filament-tables::cell
                                        class="border px-4 py-1 text-center">{{ $detail['product_name'] }}</x-filament-tables::cell>
                                    <x-filament-tables::cell
                                        class="border px-4 py-1 text-center">{{ $detail['product_code'] }}</x-filament-tables::cell>
                                    <x-filament-tables::cell
                                        class="border px-4 py-1 text-center">{{ $detail['unit_name'] }}</x-filament-tables::cell>
                                    <x-filament-tables::cell
                                        class="border px-4 py-1 text-center">{{ $detail['quantity'] }}</x-filament-tables::cell>
                                </x-filament-tables::row>
                            @endforeach
                            <tr>
                                <td colspan="5" class="py-2">
                                    <div class="h-2 bg-blue-100 rounded">
                                        <hr>

                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-filament-tables::table>
            </div>
        @else
            <div class="please_select_message_div text-center">
                <h1 class="please_select_message_text">No Supply in Store ({{ $store }}).</h1>
            </div>
        @endif
    @else
        <div class="please_select_message_div text-center">
            <h1 class="please_select_message_text">Please Choose a Store.</h1>
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

    {{-- Hide print button during printing --}}
    <style>
        @media print {
            #printReport {
                display: none;
            }
        }
    </style>
</x-filament::page>
