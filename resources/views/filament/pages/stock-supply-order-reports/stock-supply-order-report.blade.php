<x-filament::page>
    <style>
        table {
            /* border-collapse: collapse; */
            width: 100%;
            border-collapse: inherit;
            border-spacing: initial;
        }

        .fi-tabs {
            display: none !important;
        }
    </style>
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

            <table class="w-full text-sm text-left border reports table-striped" id="report-table">
                <thead class="fixed-header" style="top:64px;">
                    <tr class="header_report">
                        <th class="no_border_right text-center">
                            <img src="{{ asset('/storage/' . setting('company_logo')) }}" alt="Company Logo"
                                class="logo-img-report circle-image">
                        </th>
                        <th colspan="2" class="no_border_right_left text-left">
                            <h3>Stock Supply Report for ({{ $store }})</h3>
                            <h5>
                                Between {{ $startDate . ' & ' . $endDate }}
                            </h5>
                        </th>
                        <th class="no_border_left text-center">
                            <img class="circle-image" src="{{ asset('storage/workbench.png') }}" alt="">
                        </th>
                    </tr>
                    <tr class="bg-blue-50 text-xs text-gray-700">
                        <th class="px-4 py-2">Product</th>
                        <th class="px-4 py-2">Product Code</th>
                        <th class="px-4 py-2">Unit</th>
                        <th class="px-4 py-2">Total Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reportData as $row)
                        <tr>
                            <td
                                class="border px-4 py-1 text-center">{{ $row['product_name'] }}</td>
                            <td
                                class="border px-4 py-1 text-center">{{ $row['product_code'] }}</td>
                            <td
                                class="border px-4 py-1 text-center">{{ $row['unit_name'] }}</td>
                            <td
                                class="border px-4 py-1 text-center">{{ $row['total_quantity'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
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
