<x-filament::page>

    <div class="flex justify-end mb-4">
        <button id="printReport" class="px-6 py-2 bg-blue-500 text-white rounded">🖨️ Print</button>
    </div>

    {{ $this->getTableFiltersForm() }}

    @if ($reportData && $reportData->items() && count($reportData->items()))

        <x-filament-tables::table class="w-full text-sm text-left border reports table-striped" id="reportContent">
            <thead class="fixed-header" style="top:64px;">
                <x-filament-tables::row class="header_report bg-blue-50 text-xs text-gray-700">
                    <th class="px-4 py-2">Product</th>
                    <th class="px-4 py-2">Unit</th>
                    <th class="px-4 py-2">In Qty</th>
                    <th class="px-4 py-2">Out Qty</th>
                    <th class="px-4 py-2">In Cost</th>
                    <th class="px-4 py-2">Out Cost</th>
                    <th class="px-4 py-2">Net Cost</th>
                </x-filament-tables::row>
            </thead>
            <tbody>
                @foreach ($reportData as $row)
                    <x-filament-tables::row>
                        <x-filament-tables::cell
                            class="border px-4 py-1 text-center">{{ $row['product'] }}</x-filament-tables::cell>
                        <x-filament-tables::cell
                            class="border px-4 py-1 text-center">{{ $row['base_unit'] }}</x-filament-tables::cell>
                        <x-filament-tables::cell
                            class="border px-4 py-1 text-center">{{ $row['total_in_qty'] }}</x-filament-tables::cell>
                        <x-filament-tables::cell
                            class="border px-4 py-1 text-center">{{ $row['total_out_qty'] }}</x-filament-tables::cell>
                        <x-filament-tables::cell
                            class="border px-4 py-1 text-center">{{ $row['total_in_cost'] }}</x-filament-tables::cell>
                        <x-filament-tables::cell
                            class="border px-4 py-1 text-center">{{ $row['total_out_cost'] }}</x-filament-tables::cell>
                        <x-filament-tables::cell
                            class="px-4 py-1 font-semibold">{{ $row['net_cost'] }}</x-filament-tables::cell>
                    </x-filament-tables::row>
                @endforeach
            </tbody>
        </x-filament-tables::table>
    @else
        <div class="please_select_message_div text-center mt-10 text-gray-500">
            No data found, check for dates or store
        </div>
    @endif

    <script>
        document.getElementById("printReport").addEventListener("click", function() {
            const original = document.body.innerHTML;
            document.body.innerHTML = document.getElementById("reportContent").outerHTML;
            window.print();
            document.body.innerHTML = original;
            location.reload();
        });
    </script>
</x-filament::page>
