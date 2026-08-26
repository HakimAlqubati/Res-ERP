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

        /* Sticky Footer for Grand Total */
        tbody:last-of-type .fixed_footer,
        .fixed_footer {
            position: sticky;
            bottom: 0;
            background: #f8fafc !important;
            font-weight: bold;
            z-index: 20;
            box-shadow: 0 -2px 6px rgba(0, 0, 0, 0.08);
        }

        .dark tbody:last-of-type .fixed_footer,
        .dark .fixed_footer {
            background: #1f2937 !important;
        }

        @media print {
            #exportExcel, .fi-topbar, .fi-sidebar, .fi-header {
                display: none !important;
            }
        }
    </style>

    @if (!empty($storeId) && !empty($inventoryDate))
        @if ($reportData && count($reportData->items) > 0)
            {{-- Toolbar: Actions --}}
            <div class="flex justify-end items-center gap-2 my-4">
                <button type="button" onclick="exportToExcel()"
                    class="px-5 py-2 font-semibold rounded-md border border-emerald-600 bg-emerald-600 hover:bg-emerald-800 text-white transition duration-300 shadow-md">
                    📥 Export Excel
                </button>
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
                                <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100">Stocktake Valuation Report</h2>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                    Store: <strong class="text-gray-800 dark:text-gray-200">{{ $reportData->storeName }}</strong>
                                    &nbsp;|&nbsp;
                                      Date: <strong class="text-gray-800 dark:text-gray-200">{{ $reportData->inventoryDate }}</strong>
                                </p>
                            </th>
                            <th colspan="2" class="no_border_left text-right">
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    <span>Generated: {{ now()->format('Y-m-d H:i') }}</span>
                                </div>
                            </th>
                        </tr>

                        <tr class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                            <th class="p-3">Product Code</th>
                            <th class="p-3">Product Name</th>
                            <th class="p-3">Unit</th>
                            <th class="p-3 text-right">Qty Per Pack</th>
                            <th class="p-3 text-right">Physical Qty</th>
                            <th class="p-3 text-right">Unit Price</th>
                            <th class="p-3 text-right">Total Price</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($reportData->items as $item)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
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

                    <tbody>
                        <tr class="font-bold bg-gray-100 dark:bg-gray-800 fixed_footer text-gray-900 dark:text-gray-100">
                            <td colspan="6" class="p-3 text-right text-sm uppercase tracking-wide">
                                Grand Total Price
                            </td>
                            <td class="p-3 text-right font-mono text-base text-emerald-700 dark:text-emerald-400">
                                {{ formatMoneyWithCurrency($reportData->grandTotalValue) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
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

    {{-- Script for Excel Export --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        function exportToExcel() {
            const table = document.getElementById('valuation-table');
            if (!table) {
                alert('No table data to export.');
                return;
            }

            // Clone table to prepare for clean excel export without modifying UI
            const clone = table.cloneNode(true);

            // Remove all img elements from the clone so SheetJS parses headers cleanly
            clone.querySelectorAll('img').forEach(el => el.remove());

            const wb = XLSX.utils.table_to_book(clone, { sheet: 'Stocktake Valuation' });
            const dateStr = "{{ $reportData->inventoryDate ?? date('Y-m-d') }}";
            const filename = 'stocktake_valuation_' + dateStr + '.xlsx';
            XLSX.writeFile(wb, filename);
        }
    </script>
</x-filament::page>
