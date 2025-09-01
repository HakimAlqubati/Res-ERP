<x-filament::page>
    {{ $this->getTableFiltersForm() }}
    <style>
        table {
            /* border-collapse: collapse; */
            width: 100%;
            border-collapse: inherit;
            border-spacing: initial;
        }

        /* اجعل الترو الأخير sticky في الأسفل */
        tbody:last-of-type .fixed_footer {
            position: sticky;
            bottom: 0;
            background: white !important;
            color: #0d7c66;
            /* لون الخلفية للتأكيد */
            z-index: 10;
        }
    </style>
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

    @if (isset($storeId) || $storeId != null)

        @if (!empty($reportData))
            <div id="reportContent">
                <table class="w-full text-sm text-left pretty reports table-striped border"
                    id="report-table">
                    <thead class="fixed-header">
                        <tr class="header_report">
                            <th class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }}">


                            </th>
                            <th colspan="4" class="no_border_right_left text-center">
                                <h3>Store Position Report - Displayed in Smallest Unit</h3>
                            </th>
                            <th class="{{ app()->getLocale() == 'ar' ? 'no_border_right' : 'no_border_left' }}">
                                <img src="{{ asset('/storage/' . setting('company_logo')) }}" alt=""
                                    class="logo-left circle-image">
                            </th>
                        </tr>

                        <tr>
                            <th>Product Code</th>
                            <th>Product Name</th>
                            <th>Unit</th>

                            <th>Qty in Stock</th>
                            <th>Unit Price</th>
                            <th id="totalPriceHeader" class="cursor-pointer select-none">
                                Total Price <span id="sortIcon">⇅</span>
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($reportData as $data)
                            <tr>
                                <td>{{ $data['product_code'] }}</td>
                                <td
                                    title="{{ $data['product_id'] }}">{{ $data['product_name'] }}</td>
                                <td>{{ $data['unit_name'] }}</td>

                                <td>{{ $data['difference'] }}</td>
                                <td>{{ formatMoneyWithCurrency($data['unit_price']) }}</td>
                                <td>{{ formatMoneyWithCurrency($data['price']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tbody>
                        <tr class="font-bold bg-gray-100 fixed_footer">
                            <td colspan="5" class="text-right">Total </td>

                            <td>
                                {{ formatMoneyWithCurrency(array_sum(array_column($reportData, 'price'))) }}
                            </td>
                        </tr>
                    </tbody>

                </table>
            </div>
        @else
            <div class="please_select_message_div text-center">
                <h1 class="please_select_message_text">No data available.</h1>
            </div>
        @endif
    @else
        <div class="please_select_message_div text-center">
            <h1 class="please_select_message_text">Please select a store to view the report.</h1>
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


    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const table = document.querySelector("#report-table");
            const header = document.querySelector("#totalPriceHeader");
            const icon = document.querySelector("#sortIcon");
            let ascending = true;

            header.addEventListener("click", function() {
                const rows = Array.from(table.querySelectorAll("tbody tr"))
                    .filter(row => !row.classList.contains("font-bold")); // Ignore total row

                rows.sort((a, b) => {
                    const aValue = parseFloat(a.cells[5].innerText.replace(/[^\d.-]/g, "")) || 0;
                    const bValue = parseFloat(b.cells[5].innerText.replace(/[^\d.-]/g, "")) || 0;

                    return ascending ? aValue - bValue : bValue - aValue;
                });

                const tbody = table.querySelector("tbody");
                rows.forEach(row => tbody.appendChild(row));

                // تحديث الأيقونة
                icon.textContent = ascending ? "🔼" : "🔽";
                ascending = !ascending;
            });
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
