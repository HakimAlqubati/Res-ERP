<x-filament::page>
    {{ $this->getTableFiltersForm() }}

    <style>
        table {
            width: 100%;
            border-collapse: inherit;
            border-spacing: initial;
        }

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

        .batch-current {
            background-color: #f0fdf4 !important;
        }

        .dark .batch-current {
            background-color: rgba(34, 197, 94, 0.1) !important;
        }

        .dark tbody:last-of-type .fixed_footer {
            background: #1f2937 !important;
        }
    </style>

    {{-- Print & Export Buttons --}}
    <div class="flex justify-end gap-2 mb-4">
        <button id="printReport"
            class="px-6 py-2 font-semibold rounded-md border border-blue-600 bg-blue-500 hover:bg-blue-700 text-white transition duration-300 shadow-md">
            🖨️ Print
        </button>
        <button id="exportExcel"
            class="px-6 py-2 font-semibold rounded-md border border-green-600 bg-green-500 hover:bg-green-700 text-white transition duration-300 shadow-md">
            📥 Export Excel
        </button>
    </div>

    @if ($storeId)
        @if ($reportResult && $reportResult->totalBatches > 0)
            {{-- Summary Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-700">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Batches (All)</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($reportResult->totalBatches) }}
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-700">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Remaining Price (All)</div>
                    <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                        {{ formatMoneyWithCurrency($reportResult->totalPrice) }}
                    </div>
                </div>
            </div>

            <div id="reportContent">
                <table class="w-full text-sm text-left pretty reports table-striped border" id="report-table">
                    <thead class="fixed-header">
                        <tr class="header_report">
                            <th class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }}"></th>
                            <th colspan="8" class="no_border_right_left text-center">
                                <h3>Store Position Batch Report (FIFO)</h3>
                            </th>
                            <th class="{{ app()->getLocale() == 'ar' ? 'no_border_right' : 'no_border_left' }}">
                                <img src="{{ asset('/storage/' . setting('company_logo')) }}" alt=""
                                    class="logo-left circle-image">
                            </th>
                        </tr>

                        <tr>
                            <th>Product</th>
                            <th>Source</th>
                            <th>Date</th>
                            <th>Unit</th>
                            <th>IN Qty</th>
                            <th>OUT Qty</th>
                            <th>Current Stock</th>
                            <th>Unit Price</th>
                            <th id="totalPriceHeader" class="cursor-pointer select-none">
                                Remaining Price <span id="sortIcon">⇅</span>
                            </th>
                            <th>Batch</th>
                        </tr>
                    </thead>

                    <tbody>
                        @php $currentProductId = null; @endphp
                        @foreach ($reportResult->batches as $batch)
                            @if ($currentProductId !== $batch->product_id)
                                @php $currentProductId = $batch->product_id; @endphp
                                <tr class="bg-gray-100 dark:bg-gray-700">
                                    <td colspan="10" class="font-bold text-gray-800 dark:text-gray-200 px-4 py-2">
                                        📦 {{ $batch->product }}
                                    </td>
                                </tr>
                            @endif
                            <tr class="{{ $batch->is_current_batch ? 'batch-current' : '' }}">
                                <td>{{ $batch->product }}</td>
                                <td>{{ $batch->source_document }}</td>
                                <td>{{ $batch->movement_date }}</td>
                                <td>{{ $batch->unit }}</td>
                                <td>{{ formatQunantity($batch->base_unit_in_qty) }}</td>
                                <td>{{ formatQunantity($batch->base_unit_out) }}</td>
                                <td class="font-semibold">{{ formatQunantity($batch->current_stock) }}</td>
                                <td>{{ formatMoneyWithCurrency($batch->unit_price) }}</td>
                                <td>{{ formatMoneyWithCurrency($batch->remaining_total_price) }}</td>
                                <td>
                                    @if ($batch->is_current_batch)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            Current
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                    <tbody>
                        <tr class="font-bold bg-gray-100 fixed_footer">
                            <td colspan="8" class="text-right">Total Remaining Price</td>
                            <td>{{ formatMoneyWithCurrency($reportResult->totalPrice) }}</td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($reportResult->batches instanceof \Illuminate\Contracts\Pagination\Paginator && $reportResult->batches->hasPages())
                <div class="mt-4">
                    {{ $reportResult->batches->withQueryString()->links() }}
                </div>
            @endif
        @else
            <div class="please_select_message_div text-center">
                <h1 class="please_select_message_text">No batch data available.</h1>
            </div>
        @endif
    @else
        <div class="please_select_message_div text-center">
            <h1 class="please_select_message_text">{{ __('lang.please_select_store') }}</h1>
        </div>
    @endif

    {{-- Print --}}
    <script>
        document.getElementById("printReport")?.addEventListener("click", function() {
            const originalContent = document.body.innerHTML;
            const printContent = document.getElementById("reportContent")?.innerHTML;
            if (!printContent) return;

            document.body.innerHTML = printContent;
            window.print();
            document.body.innerHTML = originalContent;
            location.reload();
        });
    </script>

    {{-- Export Excel --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        document.getElementById("exportExcel")?.addEventListener("click", function() {
            const table = document.querySelector("#reportContent table");
            if (!table) return;

            const wb = XLSX.utils.table_to_book(table, {
                sheet: "Stock Position Batch Report"
            });
            XLSX.writeFile(wb, "stock_position_batch_report.xlsx");
        });
    </script>

    {{-- Sort by Remaining Price --}}
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const table = document.querySelector("#report-table");
            const header = document.querySelector("#totalPriceHeader");
            const icon = document.querySelector("#sortIcon");
            if (!table || !header) return;

            let ascending = true;

            header.addEventListener("click", function() {
                const rows = Array.from(table.querySelectorAll("tbody:first-of-type tr"))
                    .filter(row => !row.classList.contains("bg-gray-100") && !row.classList.contains("font-bold"));

                rows.sort((a, b) => {
                    const aValue = parseFloat(a.cells[8]?.innerText.replace(/[^\d.-]/g, "")) || 0;
                    const bValue = parseFloat(b.cells[8]?.innerText.replace(/[^\d.-]/g, "")) || 0;
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
            #printReport,
            #exportExcel {
                display: none;
            }
        }
    </style>
</x-filament::page>
