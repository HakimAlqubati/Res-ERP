<x-filament::page>
    {{ $this->getTableFiltersForm() }}
    {{-- @if (isset($branch_id)) --}}
    <tables::table class="w-full text-sm text-left pretty  ">
        <thead>

            <tables::row>
                <th colspan="3">
                    {{ __('lang.store') }}: ({{ $purchase_invoice_data['store_name'] }})
                </th>
                <th colspan="{{$show_invoice_no == true ? '4' : '3'}}">
                    {{ __('lang.supplier') }}: ({{ $purchase_invoice_data['supplier_name'] }})
                </th>
            </tables::row>
            <tables::row>
                <th>{{ __('lang.product_id') }} </th>
                <th>{{ __('lang.product') }}</th>
                <th>{{ __('lang.unit') }}</th>
                <th>{{ __('lang.quantity') }}</th>
                @if ($show_invoice_no == true)
                    <th>{{ __('lang.invoice_no') }}</th>
                @endif
                <th>{{ __('lang.unit_price') }}</th>
                <th>{{ __('lang.total_amount') }}</th>
            </tables::row>
        </thead>
        <tbody>
            @php
                $total_sub_total = 0;
                $sum_unit_price = 0;
            @endphp
            @foreach ($purchase_invoice_data['results'] as $key => $invoice_item)
                @php
                    $unit_price = $invoice_item?->unit_price;
                    $sub_total = $invoice_item?->unit_price * $invoice_item?->quantity;

                    // Add the sub_total to the totalSubTotal variable
                    $total_sub_total += $sub_total;

                    // Add the unit_price to the sumUnitPrice variable
                    $sum_unit_price += $unit_price;
                @endphp
                <tables::row>
                    <tables::cell> {{ $invoice_item?->product_id }} </tables::cell>
                    <tables::cell> {{ $invoice_item?->product_name }} </tables::cell>
                    <tables::cell> {{ $invoice_item?->unit_name }} </tables::cell>
                    <tables::cell> {{ $invoice_item?->quantity }} </tables::cell>
                    @if ($show_invoice_no == true)
                        <tables::cell>
                            {{ '(' . $invoice_item->purchase_invoice_id . ') ' . $invoice_item->invoice_no }}
                        </tables::cell>
                    @endif
                    <tables::cell> {{ $unit_price }} </tables::cell>
                    <tables::cell> {{ $sub_total }} </tables::cell>
                </tables::row>
            @endforeach

            <tables::row>
                <tables::cell colspan="{{$show_invoice_no == true ? '5' : '4'}}"> {{ __('lang.total') }} </tables::cell>
                <tables::cell> {{ $sum_unit_price }} </tables::cell>
                <tables::cell> {{ $total_sub_total }} </tables::cell>
            </tables::row>
        </tbody>

    </tables::table>
</x-filament::page>
