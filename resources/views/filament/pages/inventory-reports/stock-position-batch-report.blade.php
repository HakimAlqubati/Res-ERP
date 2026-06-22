<x-filament::page>
    {{ $this->getTableFiltersForm() }}

    <style>
        .batch-report-table {
            width: 100%;
            border-collapse: collapse;
        }

        .batch-report-table th,
        .batch-report-table td {
            border: 1px solid #e5e7eb;
            padding: 8px 12px;
            text-align: left;
        }

        .batch-report-table thead th {
            background-color: #f9fafb;
            font-weight: 600;
            white-space: nowrap;
        }

        .batch-report-table .product-group-row {
            background-color: #f3f4f6;
        }

        .batch-report-table .product-group-row td {
            font-weight: 700;
            font-size: 0.875rem;
            color: #1f2937;
            padding: 6px 12px;
        }

        .batch-report-table .batch-current-row {
            background-color: #f0fdf4;
        }

        .batch-report-table .footer-row  {
            position: sticky;
            bottom: 0;
            background: white;
            font-weight: 700;
            color: #0d7c66;
            z-index: 10;
        }
        .batch-report-table .current-page-footer  {
            position: sticky;
            bottom: 30px;
            background: white;
            font-weight: 700;
            color: #0d7c66;
            z-index: 10;
        }

        .batch-report-table tbody tr:hover {
            background-color: #f9fafb;
        }

        .summary-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }

        .summary-card .label {
            font-size: 0.8rem;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .summary-card .value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
        }

        .summary-card .value.price {
            color: #059669;
        }

        .fi-tabs {
            display: none !important;
        }

        /* Dark mode */
        .dark .batch-report-table th,
        .dark .batch-report-table td {
            border-color: #374151;
        }

        .dark .batch-report-table thead th {
            background-color: #1f2937;
            color: #e5e7eb;
        }

        .dark .batch-report-table .product-group-row {
            background-color: #374151;
        }

        .dark .batch-report-table .product-group-row td {
            color: #e5e7eb;
        }

        .dark .batch-report-table .batch-current-row {
            background-color: rgba(34, 197, 94, 0.1);
        }

        .dark .batch-report-table .footer-row {
            background: #1f2937;
        }

        .dark .batch-report-table tbody tr:hover {
            background-color: #1f2937;
        }

        .dark .summary-card {
            background: #1f2937;
            border-color: #374151;
        }

        .dark .summary-card .label {
            color: #9ca3af;
        }

        .dark .summary-card .value {
            color: #f3f4f6;
        }

        .dark .summary-card .value.price {
            color: #34d399;
        }

        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>

 

    @if ($storeId)
        @if ($reportResult && $reportResult->totalBatches > 0)
 

            <div id="reportContent">
                <table class="batch-report-table" id="report-table">
                    <thead class="fixed-header">
                        <tr class="header_report">
                            <th class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }}"></th>
                            <th colspan="7" class="no_border_right_left" style="text-align: center;">
                                <h3>Store Position Batch Report (FIFO)</h3>
                            </th>
                            <th class="{{ app()->getLocale() == 'ar' ? 'no_border_right' : 'no_border_left' }}" style="text-align: center;">
                                <img src="{{ asset('/storage/' . setting('company_logo')) }}" alt=""
                                    class="logo-left circle-image" style="display: inline-block;">
                            </th>
                        </tr>

                        <tr>
                            <th>Code</th>
                            <th>Product</th>
                            <th>Source</th>
                            <th>Date</th>
                            <th>Unit</th>
                            <th>Qty per Pack</th>
                            <th>Remaining Qty </th>
                            <th>Unit Price</th>
                            <th id="totalPriceHeader" >
                                Total Price <span ></span>
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @php $currentProductId = null; @endphp
                        @foreach ($reportResult->batches as $batch)
                            @if ($currentProductId !== $batch->product_id && 1 < 2)
                                @php $currentProductId = $batch->product_id; @endphp
                                <tr class="product-group-row">
                                    <td colspan="8">{{ $batch->product }}</td>
                                </tr>
                            @endif
                            <tr class="{{ $batch->is_current_batch ? 'batch-current-row' : '' }}">
                                <td>{{ $batch->product_code }}</td>
                                <td>{{ $batch->product }}</td>
                                <td>{{ $batch->source_document }}</td>
                                <td>{{ $batch->movement_date }}</td>
                                <td>{{ $batch->base_unit }}</td>
                                <td>{{ $batch->base_unit_package_size }}</td>
                                <td style="font-weight: 600;">{{ formatQunantity($batch->current_stock) }}</td>
                                <td>{{ formatMoneyWithCurrency($batch->unit_price) }}</td>
                                <td>{{ formatMoneyWithCurrency($batch->remaining_total_price) }}</td>
                            </tr>
                        @endforeach
                    </tbody>

                    <tbody>
                        @php
                            $currentItems = $reportResult->batches instanceof \Illuminate\Contracts\Pagination\Paginator
                                ? collect($reportResult->batches->items())
                                : collect($reportResult->batches);
                            
                            $currentTotalPrice = $currentItems->sum(function ($item) {
                                return (float) $item->remaining_total_price;
                            });
                        @endphp
                        <tr class="current-page-footer">
                            <td colspan="8" style="text-align: right;">Current Total Price</td>
                            <td>{{ formatMoneyWithCurrency($currentTotalPrice) }}</td>
                        </tr>
                        <tr class="footer-row">
                            <td colspan="8" style="text-align: right;">All Total Price</td>
                            <td>{{ formatMoneyWithCurrency($reportResult->totalPrice) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($reportResult->batches instanceof \Illuminate\Contracts\Pagination\Paginator && $reportResult->batches->hasPages())
                <div style="margin-top: 16px;" class="no-print">
                    <x-filament::pagination :paginator="$reportResult->batches" class="px-3 py-3 sm:px-6" />
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
 
</x-filament::page>
