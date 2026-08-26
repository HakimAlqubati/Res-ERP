<x-filament::page>
    {{ $this->getTableFiltersForm() }}

    <style>
        table {
            width: 100%;
            border-collapse: inherit;
            border-spacing: initial;
        }

        .fi-tabs {
            display: none !important;
        }

        tbody:last-of-type .fixed_footer {
            position: sticky;
            bottom: 0;
            background: #f8fafc !important;
            font-weight: bold;
            z-index: 10;
        }

        @media print {
            #printReport, #exportExcel, .fi-topbar, .fi-sidebar, .fi-header {
                display: none !important;
            }
        }
    </style>

    @if (!empty($storeId) && !empty($inventoryDate))
        @if ($reportData && count($reportData->items) > 0)
            {{-- Toolbar: Actions & Summary --}}
            <div class="flex flex-wrap justify-between items-center gap-3 my-4">
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 text-sm font-semibold bg-emerald-100 text-emerald-800 rounded-full dark:bg-emerald-900 dark:text-emerald-300">
                        {{ $reportData->totalItemsCount }} Products
                    </span>
                    <span class="px-3 py-1 text-sm font-semibold bg-blue-100 text-blue-800 rounded-full dark:bg-blue-900 dark:text-blue-300">
                        Total Value: {{ formatMoneyWithCurrency($reportData->grandTotalValue) }}
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <button id="printReport"
                        class="px-5 py-2 font-semibold rounded-md border border-blue-600 bg-blue-500 hover:bg-blue-700 text-white transition duration-300 shadow-md">
                        🖨️ Print
                    </button>
                    <button id="exportExcel"
                        class="px-5 py-2 font-semibold rounded-md border border-emerald-600 bg-emerald-600 hover:bg-emerald-800 text-white transition duration-300 shadow-md">
                        📥 Export Excel
                    </button>
                </div>
            </div>

            {{-- Report Content Table --}}
            <div id="reportContent" class="overflow-x-auto shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="w-full text-sm text-left pretty reports table-striped border" id="valuation-table">
                    <thead class="fixed-header">
                        <tr class="header_report">
                            <th class="no_border_right text-left" style="width: 120px;">
                                @if(setting('company_logo'))
                                    <img src="{{ asset('/storage/' . setting('company_logo')) }}" alt="Company Logo" class="logo-left circle-image" style="max-height: 50px;">
                                @endif
                            </th>
                            <th colspan="5" class="no_border_right_left text-center">
                                <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100">Stock Inventory Valuation Report</h2>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                    Store: <strong class="text-gray-800 dark:text-gray-200">{{ $reportData->storeName }}</strong>
                                    &nbsp;|&nbsp;
                                    Inventory Date: <strong class="text-gray-800 dark:text-gray-200">{{ $reportData->inventoryDate }}</strong>
                                </p>
                            </th>
                            <th colspan="2" class="no_border_left text-right">
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    <span>Generated: {{ now()->format('Y-m-d H:i') }}</span>
                                </div>
                            </th>
                        </tr>

                        <tr class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                            <th class="p-3 text-center" style="width: 50px;">#</th>
                            <th class="p-3">Product Code</th>
                            <th class="p-3 cursor-pointer select-none" id="productNameHeader">
                                Product Name <span id="productNameSortIcon">⇅</span>
                            </th>
                            <th class="p-3">Unit</th>
                            <th class="p-3 text-right">Package Size</th>
                            <th class="p-3 text-right">Physical Qty</th>
                            <th class="p-3 text-right">Unit Price</th>
                            <th class="p-3 text-right cursor-pointer select-none" id="totalValueHeader">
                                Total Value <span id="totalValueSortIcon">⇅</span>
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($reportData->items as $i => $item)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                                <td class="p-3 text-center text-gray-500">{{ $i + 1 }}</td>
                                <td class="p-3 font-mono text-xs font-semibold text-gray-700 dark:text-gray-300">
                                    {{ $item->productCode }}
                                </td>
                                <td class="p-3 font-medium text-gray-900 dark:text-gray-100" title="Product ID: {{ $item->productId }}">
                                    {{ $item->productName }}
                                </td>
                                <td class="p-3 text-gray-600 dark:text-gray-400">
                                    {{ $item->unitName }}
                                </td>
                                <td class="p-3 text-right text-gray-600 dark:text-gray-400 font-mono">
                                    {{ number_format($item->packageSize, 3) }}
                                </td>
                                <td class="p-3 text-right font-semibold text-gray-800 dark:text-gray-200 font-mono">
                                    {{ formatQunantity($item->physicalQty) }}
                                </td>
                                <td class="p-3 text-right text-gray-700 dark:text-gray-300 font-mono">
                                    {{ formatMoneyWithCurrency($item->unitPrice) }}
                                </td>
                                <td class="p-3 text-right font-bold text-emerald-700 dark:text-emerald-400 font-mono">
                                    {{ formatMoneyWithCurrency($item->totalValue) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                    <tfoot>
                        <tr class="font-bold bg-gray-100 dark:bg-gray-800 fixed_footer text-gray-900 dark:text-gray-100">
                            <td colspan="7" class="p-3 text-right text-sm uppercase tracking-wide">
                                Grand Total Valuation:
                            </td>
                            <td class="p-3 text-right font-mono text-base text-emerald-700 dark:text-emerald-400">
                                {{ formatMoneyWithCurrency($reportData->grandTotalValue) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Script for Printing --}}
            <script>
                document.getElementById('printReport').addEventListener('click', function () {
                    const originalContent = document.body.innerHTML;
                    const reportContent   = document.getElementById('reportContent').innerHTML;

                    document.body.innerHTML = reportContent;
                    window.print();
                    document.body.innerHTML = originalContent;
                    location.reload();
                });
            </script>

            {{-- Script for Excel Export --}}
            <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
            <script>
                document.getElementById('exportExcel').addEventListener('click', function () {
                    const table = document.querySelector('#reportContent table');
                    const wb    = XLSX.utils.table_to_book(table, { sheet: 'Stock Inventory Valuation' });
                    const filename = 'stock_inventory_valuation_{{ $reportData->inventoryDate }}.xlsx';
                    XLSX.writeFile(wb, filename);
                });
            </script>

            {{-- Script for Table Column Sorting --}}
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const table = document.getElementById('valuation-table');
                    if (!table) return;

                    // Sort Total Value
                    const totalValueHeader = document.getElementById('totalValueHeader');
                    const totalValueIcon   = document.getElementById('totalValueSortIcon');
                    let ascValue = true;

                    if (totalValueHeader) {
                        totalValueHeader.addEventListener('click', function () {
                            sortTable(7, ascValue, true);
                            ascValue = !ascValue;
                            totalValueIcon.textContent = ascValue ? '🔼' : '🔽';
                        });
                    }

                    // Sort Product Name
                    const productNameHeader = document.getElementById('productNameHeader');
                    const productNameIcon   = document.getElementById('productNameSortIcon');
                    let ascName = true;

                    if (productNameHeader) {
                        productNameHeader.addEventListener('click', function () {
                            sortTable(2, ascName, false);
                            ascName = !ascName;
                            productNameIcon.textContent = ascName ? '🔼' : '🔽';
                        });
                    }

                    function sortTable(columnIndex, ascending, isNumeric) {
                        const tbody = table.querySelector('tbody');
                        const rows = Array.from(tbody.querySelectorAll('tr'));

                        rows.sort((a, b) => {
                            const aCell = a.cells[columnIndex] ? a.cells[columnIndex].innerText.trim() : '';
                            const bCell = b.cells[columnIndex] ? b.cells[columnIndex].innerText.trim() : '';

                            if (isNumeric) {
                                const aNum = parseFloat(aCell.replace(/[^\d.-]/g, '')) || 0;
                                const bNum = parseFloat(bCell.replace(/[^\d.-]/g, '')) || 0;
                                return ascending ? aNum - bNum : bNum - aNum;
                            } else {
                                return ascending ? aCell.localeCompare(bCell) : bCell.localeCompare(aCell);
                            }
                        });

                        rows.forEach((row, idx) => {
                            if (row.cells[0]) row.cells[0].innerText = idx + 1;
                            tbody.appendChild(row);
                        });
                    }
                });
            </script>
        @else
            <div class="please_select_message_div text-center p-8 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mt-4">
                <h2 class="text-base font-semibold text-gray-700 dark:text-gray-300">
                    No inventory valuation records found for the selected store and date.
                </h2>
            </div>
        @endif
    @else
        <div class="please_select_message_div text-center p-8 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mt-4">
            <h2 class="text-base font-semibold text-gray-700 dark:text-gray-300">
                Please select a Store and an Inventory Date above to generate the report.
            </h2>
        </div>
    @endif
</x-filament::page>
