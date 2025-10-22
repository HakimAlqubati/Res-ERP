<x-filament-panels::page>
    <style>
      

        table {
            width: 100%;
            border-collapse: inherit;
            border-spacing: initial;
            position: relative;
            /* يساعد في طبقات z-index */
        }

        thead.fixed-header {
            position: sticky;
            top: 64px;
            z-index: 12;
            background: white;
        }

      
    </style>

    {{ $this->getTableFiltersForm() }}

    <div class="flex justify-end mb-4">
        <button onclick="exportTableToExcel('report-table')"
            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition">
            {{ __('lang.export_excel') }} 📑
        </button>
    </div>

    <div class="mb-3 p-3 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-800 rounded">
        ⚠️ <strong>Note:</strong> The prices displayed in this report are for reference only and may not be accurate.
    </div>

    <table class="w-full text-sm text-left pretty reports" id="report-table">
        <thead class="fixed-header">
            <tr>
                <th colspan="4">{{ __('lang.store') }}: ({{ $grn_data['store_name'] }})</th>
                <th colspan="{{ $show_grn_number ? '5' : '4' }}">
                    {{ __('lang.supplier') }}: ({{ $grn_data['supplier_name'] }})
                </th>
            </tr>
            <tr>
                <th>{{ __('lang.product_code') }}</th>
                <th>{{ __('lang.product') }}</th>
                <th>{{ __('lang.unit') }}</th>
                <th>{{ __('lang.quantity') }}</th>
                @if ($show_grn_number)
                    <th>{{ __('lang.grn_number') }}</th>
                @endif
                <th>{{ __('lang.supplier') }}</th>
                <th>{{ __('lang.date') }}</th>
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
            @foreach ($grn_data['results'] as $row)
                @php
                    $unit_price = $row->unit_price;
                    $sub_total = $row->unit_price * $row->quantity;
                    $total_sub_total += $sub_total;
                    $sum_unit_price += $unit_price;
                @endphp
                <tr>
                    <td>{{ $row->product_code }}</td>
                    <td>{{ $row->product_name }}</td>
                    <td>{{ $row->unit_name }}</td>
                    <td>{{ $row->quantity }}</td>

                    @if ($show_grn_number)
                        <td>{{ '(' . $row->grn_id . ') ' . $row->grn_number }}</td>
                    @endif

                    <td>{{ $row->supplier_name }}</td>
                    <td>{{ $row->grn_date }}</td>

                    @if (!isStoreManager())
                        <td>{{ formatMoneyWithCurrency($unit_price) }}</td>
                        <td>{{ formatMoneyWithCurrency($sub_total) }}</td>
                    @endif
                </tr>
            @endforeach
        </tbody>

        @if (!isStoreManager())
            <tbody class="sticky-footer">
                <tr>
                    <td colspan="{{ $show_grn_number ? '8' : '7' }}">{{ __('lang.total') }}</td>
                    <td>{{ $total_amount }}</td>
                </tr>
                <tr>
                    <td colspan="{{ $show_grn_number ? '8' : '7' }}">{{ __('lang.final_total') }}</td>
                    <td>{{ $final_total_amount }}</td>
                </tr>
            </tbody>
        @endif
    </table>

    <div class="mt-4">
        <div class="paginator_container">
            {{ $grn_data['results']->links() }}
        </div>
        <x-per-page-selector />
    </div>
</x-filament-panels::page>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
    function exportTableToExcel(tableId, filename = 'grn_report.xlsx') {
        const table = document.getElementById(tableId);
        const workbook = XLSX.utils.table_to_book(table, {
            sheet: "Report"
        });
        XLSX.writeFile(workbook, filename);
    }
</script>
