<x-filament::page>

    <style>
        .fi-tabs {
            display: none !important;
        }

        .text-center {
            text-align: center !important;
        }
    </style>
    <div class="flex justify-end mb-4">
        <button id="printReport"
            class="px-6 py-2 font-semibold rounded-md border border-blue-600 bg-blue-500 hover:bg-blue-700 transition duration-300 shadow-md">
            🖨️ Print
        </button>
    </div>

    {{ $this->getTableFiltersForm() }}

    @if (!empty($store))
        @if (!empty($reportData))
            <table class="w-full text-sm text-left border reports table-striped" id="reportContent">
                <thead class="fixed-header" style="top:64px;">
                    <tr class="header_report">
                        <th colspan="6" class="text-left text-xl font-bold px-4 py-2">
                            ({{ $store }}) To Date {{ $toDate }}
                        </th>
                    </tr>
                    <tr class="bg-blue-50 text-xs text-gray-700">
                        <th class="px-4 py-2">Product</th>
                        <th class="px-4 py-2">Code</th>
                        <th class="px-4 py-2">Unit</th>
                        <th class="px-4 py-2">In Qty</th>
                        <th class="px-4 py-2">Out Qty</th>
                        <th class="px-4 py-2">Qty in Stock</th>
                        {{-- <th class="px-4 py-2">Price (Est.)</th> --}}
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reportData as $row)
                        <tr>
                            <td class="border px-4 py-1 text-center">{{ $row['product_name'] }}</td>
                            <td class="border px-4 py-1 text-center">{{ $row['product_code'] }}</td>
                            <td class="border px-4 py-1 text-center">{{ $row['unit_name'] }}</td>
                            <td class="border px-4 py-1 text-center">{{ $row['in_qty'] }}</td>
                            <td class="border px-4 py-1 text-center">{{ $row['out_qty'] }}</td>
                            <td class="border px-4 py-1 text-center font-semibold">{{ $row['current_qty'] }}</td>
                            {{-- <td
                                class="border px-4 py-1 text-center">{{ $row['in_price'] }}</td> --}}
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="please_select_message_div text-center">
                <p class="text-center mt-10 text-gray-500">No data available for this store/date range.</p>
            </div>
        @endif
    @else
        <div class="please_select_message_div text-center">
            <p class="text-center mt-10 text-gray-500">Please select a store.</p>
        </div>
    @endif

    <script>
        document.getElementById("printReport").addEventListener("click", function() {
            const originalContent = document.body.innerHTML;
            const printContent = document.getElementById("reportContent").outerHTML;
            document.body.innerHTML = printContent;
            window.print();
            document.body.innerHTML = originalContent;
            location.reload();
        });
    </script>
</x-filament::page>
