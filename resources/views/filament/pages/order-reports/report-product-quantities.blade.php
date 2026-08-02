<x-filament::page>
    {{ $this->getTableFiltersForm() }}

    <div class="flex justify-end mt-4 mb-4 no-print">
        <button onclick="exportTableToExcel('report-table', 'Product_Quantities_Report')" style="background-color: #10b981; color: white; padding: 0.5rem 1rem; border-radius: 0.375rem; font-weight: 500; display: inline-flex; align-items: center; gap: 0.5rem;" class="hover:bg-green-600 transition shadow">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
            </svg>
            {{ __('lang.export_to_excel') ?? 'Export to Excel' }}
        </button>
    </div>

    <style>
        table {
            /* border-collapse: collapse; */
            width: 100%;
            border-collapse: inherit;
            border-spacing: initial;
        }

        /* Print-specific styles */
        @media print {

            /* Hide everything except the table */
            body * {
                visibility: hidden;
            }

            #report-table,
            #report-table * {
                visibility: visible;
            }

            #report-table {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
            }

            /* Add borders and spacing for printed tables */
            table {
                border-collapse: collapse;
                width: 100%;
            }

            th,
            td {
                border: 1px solid #000;
                padding: 10px;
                font-size: 12px;
                /* Adjust font size for better readability */
                color: #000;
                /* Black text for headers */
            }

            th {
                background-color: #ddd;
                /* Light gray background for table headers */

            }

            td {
                background-color: #fff;
                /* White background for cells */
            }

        }

        .arrow-icon {
            margin-left: 5px;
            font-size: 14px;
        }

        .cursor-pointer {
            cursor: pointer;
        }

        #report-table .footer-row td {
            position: sticky;
            bottom: 0;
            height: 46px;
            background-color: white;
            z-index: 10;
            border-top: 2px solid #e5e7eb;
            color: black;
        }

        #report-table .footer-row-page-total td {
            position: sticky;
            bottom: 46px;
            height: 46px;
            background-color: #f9fafb;
            z-index: 10;
            border-top: 2px solid #e5e7eb;
            color: black;
        }

        .dark #report-table .footer-row td,
        .dark #report-table .footer-row-page-total td {
            color: white;
        }

        .dark #report-table .footer-row td {
            background-color: #1f2937;
            border-top-color: #374151;
        }

        .dark #report-table .footer-row-page-total td {
            background-color: #111827;
            border-top-color: #374151;
        }
    </style>
    {{-- @if (isset($product_id) && is_numeric($product_id)) --}}
    <table class="w-full text-sm text-left pretty  reports" id="report-table">
        <thead class="fixed-header" style="top:64px;">





            <tr class="header_report">
                <th class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }}">
                    <p>{{ __('lang.report_product_quantities') }}</p>
                    <p>({{ isset($product_id) && is_numeric($product_id) ? \App\Models\Product::find($product_id)->name : __('lang.all') }})
                    </p>
                </th>
                <th colspan="3" class="no_border_right_left">
                    <p>{{ __('lang.start_date') . ': ' . $start_date }}</p>
                    <br>
                    <p>{{ __('lang.end_date') . ': ' . $end_date }}</p>
                </th>
                <th colspan="3" style="text-align: center; vertical-align: middle;"
                    class="{{ app()->getLocale() == 'en' ? 'no_border_left' : 'no_border_right' }}">
                    <img class="circle-image" src="{{ url('/') . '/' . 'storage/workbench.png' }}" alt="">
                </th>
            </tr>
            <tr>
                <th>{{ __('lang.branch') }}</th>
                <th>{{ __('lang.code') }}</th>
                <th>{{ __('lang.product') }}</th>
                <th>{{ __('lang.unit') }}</th>
                <th>{{ __('lang.quantity') }}</th>
                <th>{{ __('lang.price') }}</th>
                <th>{{ __('lang.subtotal') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($report_data as $data)
            <tr>
                <td> {{ $data?->branch }} </td>
                <td> {{ $data?->code }} </td>
                <td> {{ $data?->product }} </td>
                <td> {{ $data?->unit }} </td>
                <td> {{ $data?->quantity }} </td>
                <td> {{ $data?->price }} </td>
                <td> {{ $data?->subtotal }} </td>
            </tr>
            @endforeach


            <tr class="footer-row-page-total" style="font-weight: bold;">
                <td colspan="6">{{ __('lang.current_page_total') ?? 'Current Page Total' }}</td>
                <td>{{ $current_page_price_total }}</td>
            </tr>

            <tr class="footer-row" style="font-weight: bold;">
                <td colspan="6">{{ __('lang.total') }}</td>

                <td>{{ $grand_total }}</td>
            </tr>
        </tbody>

    </table>
    {{-- طباعة أزرار التنقل السلسة والخفيفة من لايف واير --}}
    @if ($report_data->hasPages())
     {{-- Pagination --}}
    <div class="mt-4 no-print">
        <x-filament::pagination
            :paginator="$report_data"
            class="px-3 py-3 sm:px-6" />
    </div>
    @endif
    {{-- @else
        <div class="please_select_message_div" style="text-align: center;">

            <h1 class="please_select_message_text">{{ __('lang.please_select_product') }}</h1>
    </div>
    @endif --}}

    <script type="text/javascript" src="https://unpkg.com/xlsx@0.15.1/dist/xlsx.full.min.js"></script>
    <script>
        function exportTableToExcel(tableID, filename = ''){
            var tableSelect = document.getElementById(tableID);
            // Clone the table to avoid modifying the original
            var clonedTable = tableSelect.cloneNode(true);
            
            // Remove any images or unwanted elements from the cloned table if needed
            var images = clonedTable.getElementsByTagName('img');
            while(images.length > 0){
                images[0].parentNode.removeChild(images[0]);
            }

            var wb = XLSX.utils.table_to_book(clonedTable, {sheet: "Report"});
            filename = filename ? filename + '.xlsx' : 'excel_data.xlsx';
            XLSX.writeFile(wb, filename);
        }
    </script>
</x-filament::page>