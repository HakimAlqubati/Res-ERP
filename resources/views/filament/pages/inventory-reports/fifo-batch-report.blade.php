<x-filament::page>
    {{ $this->getTableFiltersForm() }}
    <style>
        table {
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
            z-index: 10;
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

        <button id="exportExcel"
            class="px-6 py-2 font-semibold rounded-md border border-green-600 bg-green-500 hover:bg-green-700 transition duration-300 shadow-md">
            📥 Export Excel
        </button>
    </div>

    @if (!empty($reportData) && count($reportData) > 0)
        <div id="reportContent">
            <table class="w-full text-sm text-left pretty reports table-striped border" id="report-table">
                <thead class="fixed-header">
                    <tr class="header_report">
                        <th colspan="2" class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }}"></th>
                        <th colspan="6" class="no_border_right_left text-center">
                            <h3>FIFO Batch Report - Displayed in Smallest Unit</h3>
                        </th>
                        <th colspan="2" class="{{ app()->getLocale() == 'ar' ? 'no_border_right' : 'no_border_left' }}">
                            <img src="{{ asset('/storage/' . setting('company_logo')) }}" alt="" class="logo-left circle-image">
                        </th>
                    </tr>

                    <tr>
                        <th>Product Code</th>
                        <th>Product Name</th>
                        <th>Base Unit</th>
                        <th>Total In</th>
                        <th>Total Out</th>
                        <th>Remaining Qty</th>
                        <th>Current Batch Date</th>
                        <th>Current Price</th>
                        <th>Remaining Value</th>
                        <th id="totalPriceHeader" class="cursor-pointer select-none">
                            Total Value <span id="sortIcon">⇅</span>
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($reportData as $data)
                        <tr>
                            <td>{{ $data->productCode }}</td>
                            <td title="{{ $data->productId }}">{{ $data->productName }}</td>
                            <td>{{ $data->baseUnitName ?? 'N/A' }}</td>
                            <td>{{ $data->totalBaseEntryQty }}</td>
                            <td>{{ $data->totalBaseConsumedQty }}</td>
                            <td class="font-bold text-blue-600">{{ $data->totalBaseRemainingQty }}</td>
                            <td>{{ $data->currentBatch?->movementDate ? \Carbon\Carbon::parse($data->currentBatch->movementDate)->format('Y-m-d') : 'N/A' }}</td>
                            <td class="font-bold text-green-600">{{ $data->currentBatch ? formatMoneyWithCurrency($data->currentBatch->basePrice) : 'N/A' }}</td>
                            <td>{{ formatMoneyWithCurrency($data->totalRemainingValue) }}</td>
                            <td>{{ formatMoneyWithCurrency($data->totalInventoryValue) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tbody>
                    <tr class="font-bold bg-gray-100 fixed_footer">
                        <td colspan="8" class="text-right">Total </td>
                        <td>
                            {{ formatMoneyWithCurrency($reportData->sum('totalRemainingValue')) }}
                        </td>
                        <td>
                            {{ formatMoneyWithCurrency($reportData->sum('totalInventoryValue')) }}
                        </td>
                    </tr>
                </tbody>

            </table>
        </div>
    @else
        <div class="please_select_message_div text-center">
            <h1 class="please_select_message_text">No data available. Adjust filters to view the report.</h1>
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
                sheet: "FIFO Report"
            });
            XLSX.writeFile(wb, "fifo_batch_report.xlsx");
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const table = document.querySelector("#report-table");
            if(!table) return;
            const header = document.querySelector("#totalPriceHeader");
            const icon = document.querySelector("#sortIcon");
            let ascending = true;

            header.addEventListener("click", function() {
                const rows = Array.from(table.querySelectorAll("tbody tr"))
                    .filter(row => !row.classList.contains("font-bold")); // Ignore total row

                rows.sort((a, b) => {
                    const aValue = parseFloat(a.cells[9].innerText.replace(/[^\d.-]/g, "")) || 0;
                    const bValue = parseFloat(b.cells[9].innerText.replace(/[^\d.-]/g, "")) || 0;

                    return ascending ? aValue - bValue : bValue - aValue;
                });

                const tbody = table.querySelector("tbody");
                rows.forEach(row => tbody.appendChild(row));

                icon.textContent = ascending ? "🔼" : "🔽";
                ascending = !ascending;
            });
        });
    </script>

    <style>
        @media print {
            #printReport, #exportExcel, .fi-topbar, .fi-sidebar {
                display: none !important;
            }
        }
    </style>
</x-filament::page>
