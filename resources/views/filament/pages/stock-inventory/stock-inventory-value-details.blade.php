<x-filament::page>

    {{-- Toolbar --}}
    <div class="flex flex-wrap justify-between items-center gap-2 mb-4">
        {{-- Left: Highlight filter --}}
        <div class="flex items-center gap-3">
            <button wire:click="toggleHighlight"
                class="flex items-center gap-2 px-4 py-2 rounded-md border font-semibold text-sm transition duration-300 shadow-sm
                       {{ $highlightWrong
                            ? 'bg-red-500 border-red-600 text-white hover:bg-red-700'
                            : 'bg-gray-100 border-gray-300 text-gray-700 hover:bg-gray-200' }}">
                ⚠️ Highlight Wrong Package Size
                @if ($highlightWrong)
                    <span class="ml-1 bg-white text-red-600 rounded-full px-2 py-0.5 text-xs font-bold">
                        {{ count($wrongProductIds) }}
                    </span>
                @endif
            </button>
            @if ($highlightWrong && count($wrongProductIds) > 0)
                <span class="text-xs text-red-500 font-medium">
                    {{ count($wrongProductIds) }} product(s) with mismatched package size
                </span>
            @elseif ($highlightWrong && count($wrongProductIds) === 0)
                <span class="text-xs text-green-600 font-medium">✓ No mismatches found</span>
            @endif
        </div>

        {{-- Right: Print / Export --}}
        <div class="flex gap-2">
            <button id="printReport"
                class="px-5 py-2 font-semibold rounded-md border border-blue-600 bg-blue-500 hover:bg-blue-700 text-white transition duration-300 shadow-md">
                🖨️ Print
            </button>
            <button id="exportExcel"
                class="px-5 py-2 font-semibold rounded-md border border-green-600 bg-green-500 hover:bg-green-700 text-white transition duration-300 shadow-md">
                📥 Export Excel
            </button>
        </div>
    </div>

    @if (!empty($rows))
        <div id="reportContent">
            {{-- Header --}}
            <table class="w-full text-sm text-left border reports pretty table-striped">
                <thead>
                    <tr class="header_report">
                        <th class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }}">
                            <img src="{{ asset('/storage/' . setting('company_logo')) }}" alt="" class="logo-left circle-image">
                        </th>
                        <th colspan="6" class="no_border_right_left text-center">
                            <h3>Stock Inventory — Closing Stock Value Details</h3>
                            <p class="text-xs font-normal mt-1">
                                Store: <strong>{{ $inventory->store->name ?? 'N/A' }}</strong>
                                &nbsp;|&nbsp;
                                Date: <strong>{{ $inventory->inventory_date }}</strong>
                            </p>
                        </th>
                        <th class="{{ app()->getLocale() == 'ar' ? 'no_border_right' : 'no_border_left' }}">
                            <img class="circle-image" src="{{ url('/') . '/storage/logo/default.png' }}" alt="">
                        </th>
                    </tr>

                    <tr>
                        <th>#</th>
                        <th>Product Code</th>
                        <th>Product</th>
                        <th>Unit</th>
                        <th class="text-right">Package Size</th>
                        <th class="text-right">Physical Qty</th>
                        <th class="text-right">Unit Price</th>
                        <th class="text-right">Total Value</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($rows as $i => $row)
                        @php
                            $isWrong = $highlightWrong && in_array($row['product_id'], $wrongProductIds);
                        @endphp
                        <tr class="{{ $isWrong ? 'bg-red-50' : '' }}">
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $row['product_code'] ?? '—' }}</td>
                            <td>
                                <span class="{{ $isWrong ? 'text-red-600 font-semibold' : '' }}">
                                    {{ $row['product_name'] }}
                                </span>
                                @if ($isWrong)
                                    <span class="ml-1 text-xs bg-red-100 text-red-700 rounded px-1 py-0.5">⚠ wrong pkg</span>
                                @endif
                            </td>
                            <td>{{ $row['unit_name'] }}</td>
                            <td class="text-right">{{ number_format($row['package_size'], 3) }}</td>
                            <td class="text-right">{{ number_format($row['physical_qty'], 3) }}</td>
                            <td class="text-right">{{ formatMoneyWithCurrency($row['unit_price']) }}</td>
                            <td class="text-right">{{ formatMoneyWithCurrency($row['total_value']) }}</td>
                        </tr>
                    @endforeach
                </tbody>

                <tfoot>
                    <tr class="font-bold bg-gray-100">
                        <td colspan="7" class="text-right pr-2">Grand Total</td>
                        <td class="text-right">{{ formatMoneyWithCurrency($grandTotal) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @else
        <div class="please_select_message_div text-center">
            <h1 class="please_select_message_text">No details available for this inventory.</h1>
        </div>
    @endif

    {{-- Print --}}
    <script>
        document.getElementById('printReport').addEventListener('click', function () {
            const original = document.body.innerHTML;
            const content  = document.getElementById('reportContent').innerHTML;
            document.body.innerHTML = content;
            window.print();
            document.body.innerHTML = original;
            location.reload();
        });
    </script>

    {{-- Export Excel --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        document.getElementById('exportExcel').addEventListener('click', function () {
            const table = document.querySelector('#reportContent table');
            const wb    = XLSX.utils.table_to_book(table, { sheet: 'Stock Value Details' });
            XLSX.writeFile(wb, 'stock_inventory_value_details.xlsx');
        });
    </script>

    <style>
        @media print {
            #printReport, #exportExcel { display: none; }
        }
    </style>

</x-filament::page>
