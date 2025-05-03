<x-filament::page>

    <div class="flex justify-end mb-4">


        <button id="printReport"
            class="px-6 py-2 ml-4 font-semibold rounded-md border border-blue-600 bg-blue-500 hover:bg-blue-700 transition duration-300 shadow-md">
            🖨️ Print
        </button>
    </div>
    {{ $this->getTableFiltersForm() }}
    <div id="reportContent">
        @if (!empty($reportData))
            <x-filament-tables::table class="w-full text-sm text-left pretty reports table-striped border">
                <thead>
                    <x-filament-tables::row class="header_report">
                        @if (!$groupByOrder)
                            <th>Order ID</th>
                        @endif
                        <th>Product Code</th>
                        <th>Product Name</th>
                        <th>Unit ID</th>
                        <th>Unit Name</th>
                        <th>Quantity</th>
                        @if (!$groupByOrder)
                            <th>Created At</th>
                        @endif
                    </x-filament-tables::row>
                </thead>
                <tbody>
                    @foreach ($reportData as $row)
                        @if (isset($row['details']))
                            {{-- Grouped --}}
                            @foreach ($row['details'] as $detail)
                                <x-filament-tables::row>
                                    @if (!$groupByOrder)
                                        <x-filament-tables::cell>{{ $row['order_id'] }}</x-filament-tables::cell>
                                    @endif
                                    <x-filament-tables::cell>{{ $detail['product_code'] }}</x-filament-tables::cell>
                                    <x-filament-tables::cell>{{ $detail['product_name'] }}</x-filament-tables::cell>
                                    <x-filament-tables::cell>{{ $detail['unit_id'] }}</x-filament-tables::cell>
                                    <x-filament-tables::cell>{{ $detail['unit_name'] }}</x-filament-tables::cell>
                                    <x-filament-tables::cell
                                        class="font-bold">{{ $detail['quantity'] }}</x-filament-tables::cell>
                                    @if (!$groupByOrder)
                                        <x-filament-tables::cell
                                            class="font-bold">{{ $detail['created_at'] }}</x-filament-tables::cell>
                                    @endif
                                </x-filament-tables::row>
                            @endforeach
                        @else
                            {{-- Ungrouped --}}
                            <x-filament-tables::row>
                                @if (!$groupByOrder)
                                    <x-filament-tables::cell>{{ $row['order_id'] }}</x-filament-tables::cell>
                                @endif
                                <x-filament-tables::cell>{{ $row['product_code'] }}</x-filament-tables::cell>
                                <x-filament-tables::cell>{{ $row['product_name'] }}</x-filament-tables::cell>
                                <x-filament-tables::cell>{{ $row['unit_id'] }}</x-filament-tables::cell>
                                <x-filament-tables::cell>{{ $row['unit_name'] }}</x-filament-tables::cell>
                                <x-filament-tables::cell
                                    class="font-bold">{{ $row['quantity'] }}</x-filament-tables::cell>


                                @if (!$groupByOrder)
                                    <x-filament-tables::cell
                                        class="font-bold">{{ $row['created_at'] }}</x-filament-tables::cell>
                                @endif
                            </x-filament-tables::row>
                        @endif
                    @endforeach
                </tbody>
            </x-filament-tables::table>
        @else
            <div class="text-center mt-8">
                <h1 class="text-gray-500">No data available.</h1>
            </div>
        @endif
    </div>

    {{-- Print Script --}}
    <script>
        document.getElementById("printReport").addEventListener("click", function() {
            const originalContent = document.body.innerHTML;
            const printContent = document.getElementById("reportContent").innerHTML;
            document.body.innerHTML = printContent;
            window.print();
            document.body.innerHTML = originalContent;
            location.reload();
        });
    </script>

    <style>
        @media print {
            #printReport {
                display: none;
            }
        }
    </style>

</x-filament::page>
