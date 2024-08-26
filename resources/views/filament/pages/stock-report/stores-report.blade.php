<x-filament::page>
    {{ $this->getTableFiltersForm() }}
    {{-- @if (isset($branch_id)) --}}
    <tables::table class="w-full text-sm text-left pretty  ">
        <thead>

            <tables::row class="header_report">
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
                        src="https://w7.pngwing.com/pngs/882/726/png-transparent-chef-cartoon-chef-photography-cooking-fictional-character-thumbnail.png"
                        alt="">
                </th>
            </tables::row>
            <tables::row>
                <th>{{ __('lang.product_id') }} </th>
                <th>{{ __('lang.product') }}</th>
                <th>{{ __('lang.unit') }}</th>
                <th>{{ __('lang.purchased_qty') }}</th>
                <th>{{ __('lang.qty_sent_to_branches') }}</th>
                <th>{{ __('lang.qty_in_stock') }}</th>
            </tables::row>
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
                <tables::row>
                    <tables::cell> {{ $report_item?->product_id }} </tables::cell>
                    <tables::cell> {{ $report_item?->product_name }} </tables::cell>
                    <tables::cell> {{ $report_item?->unit_name }} </tables::cell>
                    <tables::cell> {{ $report_item?->income }} </tables::cell>
                    <tables::cell> {{ $report_item?->ordered }} </tables::cell>
                    <tables::cell> {{ $report_item?->remaining }} </tables::cell>
                </tables::row>
            @endforeach
            <tables::row>
                <tables::cell colspan="3"> {{ __('lang.total') }} </tables::cell>
                <tables::cell> {{$total_income}} </tables::cell> 
                <tables::cell> {{$total_ordered}} </tables::cell> 
                <tables::cell> {{$total_remaining}} </tables::cell> 
            </tables::row>
        </tbody>

    </tables::table>
</x-filament::page>
