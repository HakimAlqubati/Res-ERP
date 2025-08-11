<x-filament::page>
    {{ $this->getTableFiltersForm() }}

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
    </style>
    {{-- @if (isset($product_id) && is_numeric($product_id)) --}}
    <x-filament-tables::table class="w-full text-sm text-left pretty  reports" id="report-table">
        <thead class="fixed-header" style="top:64px;">





            <x-filament-tables::row class="header_report">
                <th class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }}">
                    <p>{{ __('lang.report_product_quantities') }}</p>
                    <p>({{ isset($product_id) && is_numeric($product_id) ? \App\Models\Product::find($product_id)->name : __('lang.all') }})
                    </p>
                </th>
                <th colspan="2" class="no_border_right_left">
                    <p>{{ __('lang.start_date') . ': ' . $start_date }}</p>
                    <br>
                    <p>{{ __('lang.end_date') . ': ' . $end_date }}</p>
                </th>
                <th colspan="2" style="text-align: center; vertical-align: middle;"
                    class="{{ app()->getLocale() == 'en' ? 'no_border_left' : 'no_border_right' }}">
                    <img class="circle-image" src="{{ url('/') . '/' . 'storage/workbench.png' }}" alt="">
                </th>
            </x-filament-tables::row>
            <x-filament-tables::row>
                <th>{{ __('lang.code') }}</th>
                <th>{{ __('lang.product') }}</th>
                <th>{{ __('lang.unit') }}</th>
                <th>{{ __('lang.quantity') }}</th>
                <th>{{ __('lang.price') }}</th>
            </x-filament-tables::row>
        </thead>
        <tbody>
            @foreach ($report_data as $data)
                <x-filament-tables::row>

                    <x-filament-tables::cell> {{ $data?->code }} </x-filament-tables::cell>
                    <x-filament-tables::cell> {{ $data?->product }} </x-filament-tables::cell>
                    <x-filament-tables::cell> {{ $data?->unit }} </x-filament-tables::cell>
                    <x-filament-tables::cell> {{ $data?->quantity }} </x-filament-tables::cell>
                    <x-filament-tables::cell> {{ $data?->price }} </x-filament-tables::cell>


                </x-filament-tables::row>
            @endforeach

            {{-- <x-filament-tables::row>
                <x-filament-tables::cell colspan="3"> {{ __('lang.total') }} </x-filament-tables::cell>

                <x-filament-tables::cell> {{ $total_quantity }} </x-filament-tables::cell>
                <x-filament-tables::cell> {{ $total_price }} </x-filament-tables::cell>
            </x-filament-tables::row> --}}
        </tbody>

    </x-filament-tables::table>
    {{-- @else
        <div class="please_select_message_div" style="text-align: center;">

            <h1 class="please_select_message_text">{{ __('lang.please_select_product') }}</h1>
        </div>
    @endif --}}
</x-filament::page>
