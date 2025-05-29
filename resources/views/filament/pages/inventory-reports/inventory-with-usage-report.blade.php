<x-filament::page>

    {{ $this->getTableFiltersForm() }}
    <style>
        table {
            /* border-collapse: collapse; */
            width: 100%;
            border-collapse: inherit;
            border-spacing: initial;
        }
    </style>
    <div class="flex justify-end mb-4">
        <button id="printReport"
            class="px-6 py-2 font-semibold rounded-md border border-blue-600 bg-blue-500 hover:bg-blue-700 transition duration-300 shadow-md">
            🖨️ Print
        </button>
    </div>

    @if ($storeId)
        @if (count($reportData) > 0)
            <div id="reportContent">
                <x-filament-tables::table class="w-full text-sm text-left pretty reports table-striped border">
                    <thead class="fixed-header">
                        <x-filament-tables::row class="header_report">
                            <th colspan="3"
                                class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }}">
                            </th>
                            <th colspan="3" class="no_border_right no_border_left">


                                <h3>Inventory With Usage Report</h3>
                            </th>
                            <th colspan="3"
                                class="{{ app()->getLocale() == 'ar' ? 'no_border_right' : 'no_border_left' }}"
                                style="text-align: center;">
                                <img style="display: inline-block;"
                                    src="{{ asset('/storage/' . setting('company_logo') . '') }}" alt=""
                                    class="logo-left circle-image">
                            </th>
                        </x-filament-tables::row>
                        <x-filament-tables::row>
                            <th>Product Code</th>
                            <th>Product Name</th>
                            <th>Unit Name</th>
                            <th>Package Size</th>
                            <th>Ordered Qty</th>
                            <th>Used Qty</th>
                            <th>Remaining Qty</th>
                            <th>Price</th>
                            <th>Total Price</th>
                        </x-filament-tables::row>
                    </thead>
                    <tbody>
                        @foreach ($reportData as $productReport)
                            @foreach ($productReport as $data)
                                <x-filament-tables::row>
                                    <x-filament-tables::cell
                                        class="border px-4 py-2">{{ $data['product_code'] }}</x-filament-tables::cell>
                                    <x-filament-tables::cell
                                        class="border px-4 py-2">{{ $data['product_name'] }}</x-filament-tables::cell>
                                    <x-filament-tables::cell
                                        class="border px-4 py-2">{{ $data['unit_name'] }}</x-filament-tables::cell>
                                    <x-filament-tables::cell
                                        class="border px-4 py-2">{{ $data['package_size'] }}</x-filament-tables::cell>
                                    <x-filament-tables::cell
                                        class="border px-4 py-2 text-red-600">{{ $data['ordered_quantity'] }}</x-filament-tables::cell>
                                    <x-filament-tables::cell
                                        class="border px-4 py-2 text-red-600">{{ $data['used_quantity'] ?? '-' }}</x-filament-tables::cell>
                                    <x-filament-tables::cell
                                        class="border px-4 py-2 font-bold">{{ $data['remaining_qty'] }}</x-filament-tables::cell>
                                    <x-filament-tables::cell
                                        class="border px-4 py-2 font-bold">{{ $data['price'] }}</x-filament-tables::cell>
                                    <x-filament-tables::cell
                                        class="border px-4 py-2 font-bold">{{ $data['total_price'] }}</x-filament-tables::cell>
                                </x-filament-tables::row>
                            @endforeach
                        @endforeach
                    </tbody>
                    @if (isset($showSmallestUnit) && $showSmallestUnit)
                        <tbody>
                            <x-filament-tables::row>
                                <x-filament-tables::cell colspan="7">
                                    <strong>Total </strong>
                                </x-filament-tables::cell>
                                <x-filament-tables::cell class="border px-4 py-2 font-bold">
                                    {{ $final_price }}
                                </x-filament-tables::cell>
                                <x-filament-tables::cell class="border px-4 py-2 font-bold">
                                    {{ $final_total_price }}
                                </x-filament-tables::cell>
                            </x-filament-tables::row>
                        </tbody>
                    @endif
                </x-filament-tables::table>
            </div>

            <div class="mt-4">
                @if (isset($pagination) && $pagination instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    {{-- {{ $pagination->links() }} --}}
                @endif
                {{-- <div class="flex justify-end mb-2">
                    <form method="GET">
                        <label for="perPage" class="mr-2 font-semibold text-sm">Items per page:</label>
                        <select name="perPage" id="perPage" onchange="this.form.submit()"
                            class="border px-3 py-1 rounded-md text-sm">
                            @foreach ([5, 10, 15, 20, 30, 50, 'all'] as $option)
                                <option value="{{ $option }}"
                                    {{ request('perPage', 15) == $option ? 'selected' : '' }}>
                                    {{ is_numeric($option) ? $option : 'All' }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div> --}}
            </div>
        @else
            <div class="text-center">
                <h1 class="text-lg font-semibold">No inventory usage data available.</h1>
            </div>
        @endif
    @else
        <div class="text-center">
            <h1 class="text-lg font-semibold">{{ __('lang.please_select_store') }}</h1>
        </div>
    @endif

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
