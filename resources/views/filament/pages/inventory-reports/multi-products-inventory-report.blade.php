<x-filament::page>
    {{-- Print Button --}}
    <style>
        table {
            /* border-collapse: collapse; */
            width: 100%;
            border-collapse: inherit;
            border-spacing: initial;
        }

        .fi-tabs {
            display: none !important;
        }
    </style>

    <div class="flex justify-end gap-3 mb-4">


        <button id="printReport"
            class="px-6 py-2 font-semibold rounded-md border border-blue-600 bg-blue-500 hover:bg-blue-700  transition duration-300 shadow-md">
            🖨️ Print
        </button>


    </div>

    {{ $this->getTableFiltersForm() }}
    @if (isset($storeId) || $storeId != null)

        @if (count($reportData) > 0)
            <div id="reportContent">

                <x-filament-tables::table class="w-full text-sm text-left pretty reports table-striped border">
                    <thead class="fixed-header">
                        <x-filament-tables::row class="header_report">
                            <th class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }}">

                                <div style="width: 100%;">


                                </div>
                            </th>
                            <th colspan="2" class="no_border_right_left text-center">
                                <h3>Inventory Report</h3>
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
                            <th>Unit ID</th>
                            <th>Unit Name</th>
                            <th>Package Size</th>
                            <th>Quantity in Stock</th>
                        </x-filament-tables::row>
                    </thead>
                    <tbody>
                        @foreach ($reportData as $productReport)
                            @foreach ($productReport as $data)
                                <x-filament-tables::row>
                                    <x-filament-tables::cell class="border border-gray-300 px-4 py-2"
                                        title="{{ $data['product_id'] }}">
                                        <strong>{{ $data['product_code'] }}</strong>
                                    </x-filament-tables::cell>
                                    <x-filament-tables::cell class="border border-gray-300 px-4 py-2"
                                        title="{{ $data['product_id'] }}">
                                        <strong>{{ $data['product_name'] }}</strong>
                                    </x-filament-tables::cell>
                                    <x-filament-tables::cell class="border border-gray-300 px-4 py-2">
                                        {{ $data['unit_id'] }}
                                    </x-filament-tables::cell>
                                    <x-filament-tables::cell class="border border-gray-300 px-4 py-2">
                                        {{ $data['unit_name'] }}
                                    </x-filament-tables::cell>
                                    <x-filament-tables::cell class="border border-gray-300 px-4 py-2">
                                        {{ $data['package_size'] }}
                                    </x-filament-tables::cell>
                                    <x-filament-tables::cell class="border border-gray-300 px-4 py-2 font-bold">
                                        {{ $data['remaining_qty'] }}
                                    </x-filament-tables::cell>
                                </x-filament-tables::row>
                            @endforeach
                        @endforeach
                    </tbody>
                </x-filament-tables::table>


            </div>

            {{-- Pagination Controls --}}
            {{-- @if ($reportData instanceof \Illuminate\Pagination\LengthAwarePaginator) --}}
            <div class="mt-4">
                <div class="paginator_container">
                    @if (isset($pagination) && $pagination instanceof \Illuminate\Pagination\LengthAwarePaginator)
                        {{ $pagination->links() }}
                    @endif
                </div>


                <x-per-page-selector />
            </div>
            {{-- @endif --}}
        @else
            <div class="please_select_message_div text-center">
                <h1 class="please_select_message_text">No inventory data available.</h1>
            </div>
        @endif
    @else
        <div class="please_select_message_div" style="text-align: center;">

            <h1 class="please_select_message_text">{{ __('lang.please_select_store') }}</h1>
        </div>
    @endif
    {{-- JavaScript to Handle Printing --}}
    <script>
        document.getElementById("printReport").addEventListener("click", function() {
            const originalContent = document.body.innerHTML;
            const printContent = document.getElementById("reportContent").innerHTML;

            document.body.innerHTML = printContent;
            window.print();
            document.body.innerHTML = originalContent;
            location.reload(); // Reload to restore the page
        });
    </script>

    {{-- CSS to Hide Button in Print Mode --}}
    <style>
        @media print {
            #printReport {
                display: none;
            }
        }
    </style>

</x-filament::page>
