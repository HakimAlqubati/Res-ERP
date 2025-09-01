<x-filament::page>

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
            <table class="w-full text-sm text-left pretty reports table-striped border">
                <thead>
                    <tr class="header_report">
                        <th class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }}">
                            <img src="{{ asset('/storage/' . setting('company_logo')) }}" alt=""
                                class="logo-left circle-image">
                        </th>
                        <th colspan="2" class="no_border_right_left text-center">
                            <h3>Order Product Details</h3>
                        </th>
                        <th class="{{ app()->getLocale() == 'ar' ? 'no_border_right' : 'no_border_left' }}">
                            <img class="circle-image" src="{{ url('/') . '/storage/logo/default.png' }}" alt="">
                        </th>
                    </tr>

                    <tr>
                        <th>Product ID</th>
                        <th>Product Name</th>
                        <th>Unit</th>
                        <th>Qty per Pack</th>
                        <th>Ordered Qty</th>
                        {{-- <th>Unit Price</th> --}}
                    </tr>
                </thead>

                <tbody>
                    @foreach ($reportData as $data)
                        @php
                            $data = is_object($data) ? (array) $data : $data;
                        @endphp
                        <tr>
                            <td>{{ $data['product_id'] }}</td>
                            <td>{{ $data['p_name'] }}</td>
                            <td>{{ $data['unit'] }}</td>
                            <td>{{ $data['package_size'] }}</td>
                            <td>{{ $data['qty'] }}</td>

                            {{-- <td>{{ $data['price'] }}</td> --}}
                        </tr>
                    @endforeach
                </tbody>
            </table>
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
