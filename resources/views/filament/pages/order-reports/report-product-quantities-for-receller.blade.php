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
    @if (is_array($report_data) && count($report_data) > 0)
        <table class="w-full text-sm text-left pretty  reports" id="report-table">
            <thead class="fixed-header" style="top:64px;">





                <tr class="header_report">
                    <th class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }}">
                        <p>{{ 'Delevery Order Report' }}</p>
                        <p> {{ $branch ?? '' }} </p>
                        {{-- <p>({{ isset($product_id) && is_numeric($product_id) ? \App\Models\Product::find($product_id)->name : __('lang.all') }}) --}}
                        </p>
                    </th>
                    <th colspan="3" class="no_border_right_left">
                        <p>{{ __('lang.start_date') . ': ' . $start_date ?? 'Unspecified' }}</p>
                        <br>
                        <p>{{ __('lang.end_date') . ': ' . $end_date ?? 'Unspecified' }}</p>
                    </th>
                    <th colspan="2" style="text-align: center; vertical-align: middle;"
                        class="{{ app()->getLocale() == 'en' ? 'no_border_left' : 'no_border_right' }}">
                        <img class="circle-image" src="{{ url('/') . '/' . 'storage/workbench.png' }}" alt="">
                    </th>
                </tr>

                <tr>
                    <th>{{ __('lang.reseller') ?? 'Reseller' }}</th> {{-- العمود الجديد --}}
                    <th>{{ __('lang.code') ?? 'Code' }}</th>
                    <th>{{ __('lang.product') ?? 'Product' }}</th>
                    <th>{{ __('lang.unit') ?? 'Unit' }}</th>
                    <th>{{ __('lang.package_size') ?? 'Package Size' }}</th>
                    <th>{{ __('lang.quantity') ?? 'Quantity' }}</th>
                    {{-- <th>{{ __('lang.price') ?? 'Price' }}</th> --}}

                </tr>
            </thead>
            <tbody> @php
                $total_quantity = 0;
            @endphp
                @foreach ($report_data as $data)
                    @php
                        $total_quantity += $data?->in_quantity ?? 0;
                    @endphp
                    <tr>
                            <td>{{ $data->branch }}</td>
                        <td> {{ $data?->code }} </td>
                        <td> {{ $data?->product }} </td>
                        <td> {{ $data?->unit }} </td>
                        <td> {{ $data?->package_size }} </td>
                        <td> {{ $data?->in_quantity }} </td>
                        {{-- <td> {{ $data?->price }} </td> --}}


                    </tr>
                @endforeach
                <tr>
                    <td colspan="5" style="text-align: right; font-weight: bold;">
                        {{ __('lang.total') }}
                    </td>
                    <td style="font-weight: bold;">
                        {{ formatQunantity($total_quantity) }}
                    </td>
                </tr>
            </tbody>

        </table>
    @else
        <div class="please_select_message_div" style="text-align: center;">

            <h1 class="please_select_message_text">{{ __('lang.no_data') }}</h1>
        </div>
    @endif
</x-filament::page>
