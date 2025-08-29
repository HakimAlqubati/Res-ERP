<x-filament::page>
    {{ $this->getTableFiltersForm() }}
    {{-- @if (isset($branch_id)) --}}
    <table class="w-full text-sm text-left pretty  ">
        <thead>

            <tr class="header_report">
                <th colspan="2" class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }}">
                    <p>{{ __('lang.store') }}:
                        ({{ isset($store_id) && is_numeric($store_id) ? \App\Models\Store::find($store_id)->name : __('lang.all_stores') }})
                    </p>
                    <p>{{ __('lang.supplier') }}:
                        ({{ isset($supplier_id) && is_numeric($supplier_id) ? \App\Models\User::find($supplier_id)->name : __('lang.all_suppliers') }})
                    </p>
                </th>
                <th colspan="3" class="no_border_right_left" style="text-align: center; vertical-align: middle;">
                    <h3>({{ __('lang.stores_report') }})</h3>
                </th>
                <th colspan="1" style="text-align: center; vertical-align: middle;"
                    class="{{ app()->getLocale() == 'en' ? 'no_border_left' : 'no_border_right' }}">
                    <img class="circle-image"
                        src="{{ url('/') . '/storage/logo/default.png' }}"
                        alt="">
                </th>
            </tr>
            <tr>
                <th>{{ __('lang.product_id') }} </th>
                <th>{{ __('lang.product') }}</th>
                <th>{{ __('lang.unit') }}</th>
                <th>{{ __('lang.purchased_qty') }}</th>
                <th>{{ __('lang.qty_sent_to_branches') }}</th>
                <th>{{ __('lang.qty_in_stock') }}</th>
            </tr>
        </thead>
        <tbody>

            @php
                $total_income = 0;
                $total_ordered = 0;
                $total_remaining = 0;
            @endphp
            @foreach ($stores_report_data as $key => $report_item)
                @php
                    $total_income += $report_item?->income;
                    $total_ordered += $report_item?->ordered;
                    $total_remaining += $report_item?->remaining;
                @endphp
                <tr>
                    <td> {{ $report_item?->product_id }} </td>
                    <td> {{ $report_item?->product_name }} </td>
                    <td> {{ $report_item?->unit_name }} </td>
                    <td> {{ $report_item?->income }} </td>
                    <td> {{ $report_item?->ordered }} </td>
                    <td> {{ $report_item?->remaining }} </td>
                </tr>
            @endforeach
            <tr>
                <td colspan="3"> {{ __('lang.total') }} </td>
                <td> {{$total_income}} </td> 
                <td> {{$total_ordered}} </td> 
                <td> {{$total_remaining}} </td> 
            </tr>
        </tbody>

    </table>
</x-filament::page>
