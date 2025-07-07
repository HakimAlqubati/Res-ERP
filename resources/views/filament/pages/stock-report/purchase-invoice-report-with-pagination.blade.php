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

        button {
            background: #0d7c66;
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
    <x-filament-tables::table class="w-full text-sm text-left pretty reports" id="report-table">
        <thead style="top:64px;" class="fixed-header">

            <x-filament-tables::row>
                <th colspan="4">
                    {{ __('lang.store') }}: ({{ $purchase_invoice_data['store_name'] }})
                </th>
                <th colspan="{{ $show_invoice_no == true ? '5' : '4' }}">
                    {{ __('lang.supplier') }}: ({{ $purchase_invoice_data['supplier_name'] }})
                </th>
            </x-filament-tables::row>
            <x-filament-tables::row>
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
            </x-filament-tables::row>
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
                <x-filament-tables::row>
                    <x-filament-tables::cell> {{ $invoice_item->product_code }} </x-filament-tables::cell>
                    <x-filament-tables::cell> {{ $invoice_item->product_name }} </x-filament-tables::cell>
                    <x-filament-tables::cell> {{ $invoice_item->unit_name }} </x-filament-tables::cell>
                    <x-filament-tables::cell> {{ $invoice_item->quantity }} </x-filament-tables::cell>
                    @if ($show_invoice_no == true)
                        <x-filament-tables::cell>
                            {{ '(' . $invoice_item->purchase_invoice_id . ') ' . $invoice_item->invoice_no }}
                        </x-filament-tables::cell>
                    @endif
                    <x-filament-tables::cell>
                        {{ $invoice_item->supplier_name }}
                    </x-filament-tables::cell>
                    <x-filament-tables::cell>
                        {{ $invoice_item->purchase_date }}
                    </x-filament-tables::cell>
                    @if (!isStoreManager())
                        <x-filament-tables::cell> {{ formatMoneyWithCurrency($unit_price) }} </x-filament-tables::cell>
                        <x-filament-tables::cell> {{ formatMoneyWithCurrency($sub_total) }} </x-filament-tables::cell>
                    @endif
                </x-filament-tables::row>
            @endforeach

        </tbody>

        @if (!isStoreManager())
            <tbody>
                <x-filament-tables::row class="fixed_footer">
                    <x-filament-tables::cell colspan="{{ $show_invoice_no ? '8' : '7' }}"> {{ __('lang.total') }}
                    </x-filament-tables::cell>
                    {{-- <x-filament-tables::cell> {{ formatMoneyWithCurrency($sum_unit_price) }} </x-filament-tables::cell> --}}
                    <x-filament-tables::cell> {{ $total_amount }}
                    </x-filament-tables::cell>
                </x-filament-tables::row>
            </tbody>
        @endif

        {{-- 🔹 Add Pagination Links --}}
        <tr>
            <td colspan="100%">
                {{-- {{ $purchase_invoice_data['results']->links() }} --}}
            </td>
        </tr>





    </x-filament-tables::table>
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
