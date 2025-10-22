<x-filament-panels::page>
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
    {{ $this->getTableFiltersForm() }}
    <div class="flex justify-end mb-4">
        <button onclick="exportTableToExcel('report-table')"
            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition">
            {{ __('lang.export_excel') }}
            📑
        </button>
    </div>
    {{-- @if (isset($branch_id)) --}}
    <table class="w-full text-sm text-left pretty reports" id="report-table">
        <thead style="top:64px;" class="fixed-header">

            <tr>
                <th colspan="4">
                    {{ __('lang.store') }}: ({{ $purchase_invoice_data['store_name'] }})
                </th>
                <th colspan="{{ $show_invoice_no == true ? '5' : '4' }}">
                    {{ __('lang.supplier') }}: ({{ $purchase_invoice_data['supplier_name'] }})
                </th>
            </tr>
            <tr>
                <th>{{ __('lang.product_code') }} </th>
                <th>{{ __('lang.product') }}</th>
                <th>{{ __('lang.unit') }}</th>
                <th>{{ __('lang.quantity') }}</th>
                @if ($show_invoice_no == true)
                    <th>{{ __('lang.invoice_no') }}</th>
                @endif
                <th>{{ __('lang.supplier') }}</th>
                <th> {{ __('lang.date') }} </th>
                @if (!isStoreManager())
                    <th>{{ __('lang.unit_price') }}</th>
                    <th>{{ __('lang.total_amount') }}</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @php
                $total_sub_total = 0;
                $sum_unit_price = 0;
            @endphp
            @foreach ($purchase_invoice_data['results'] as $invoice_item)
                @php
                    $unit_price = $invoice_item->unit_price;
                    $sub_total = $invoice_item->unit_price * $invoice_item->quantity;

                    $total_sub_total += $sub_total;
                    $sum_unit_price += $unit_price;
                @endphp
                <tr>
                    <td> {{ $invoice_item->product_code }} </td>
                    <td> {{ $invoice_item->product_name }} </td>
                    <td> {{ $invoice_item->unit_name }} </td>
                    <td> {{ $invoice_item->quantity }} </td>
                    @if ($show_invoice_no == true)
                        <td>
                            {{ '(' . $invoice_item->purchase_invoice_id . ') ' . $invoice_item->invoice_no }}
                        </td>
                    @endif
                    <td>
                        {{ $invoice_item->supplier_name }}
                    </td>
                    <td>
                        {{ $invoice_item->purchase_date }}
                    </td>
                    @if (!isStoreManager())
                        <td> {{ formatMoneyWithCurrency($unit_price) }} </td>
                        <td> {{ formatMoneyWithCurrency($sub_total) }} </td>
                    @endif
                </tr>
            @endforeach

        </tbody>

        @if (!isStoreManager())
            <tbody class="sticky-footer">
                <tr>
                    <td colspan="{{ $show_invoice_no ? '8' : '7' }}"> {{ __('lang.total') }}
                    </td>
                    {{-- <td> {{ formatMoneyWithCurrency($sum_unit_price) }} </td> --}}
                    <td> {{ $total_amount }}
                    </td>
                </tr>
                <tr>
                    <td colspan="{{ $show_invoice_no ? '8' : '7' }}"> {{ __('lang.final_total') }}
                    </td>
                    {{-- <td> {{ formatMoneyWithCurrency($sum_unit_price) }} </td> --}}
                    <td> {{ $final_total_amount }}
                    </td>
                </tr>

            </tbody>
        @endif

        {{-- 🔹 Add Pagination Links --}}
        <tr>
            <td colspan="100%">
                {{-- {{ $purchase_invoice_data['results']->links() }} --}}
            </td>
        </tr>





    </table>
    <div class="mt-4">
        <div class="paginator_container">
            {{ $purchase_invoice_data['results']->links() }}
        </div>
        <x-per-page-selector />
    </div>


</x-filament-panels::page>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
    function exportTableToExcel(tableId, filename = 'purchase_invoice_report.xlsx') {
        const table = document.getElementById(tableId);
        const workbook = XLSX.utils.table_to_book(table, {
            sheet: "Report"
        });
        XLSX.writeFile(workbook, filename);
    }
</script>
