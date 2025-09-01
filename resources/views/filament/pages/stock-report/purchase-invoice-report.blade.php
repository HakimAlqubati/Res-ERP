<x-filament-panels::page>
    {{ $this->getTableFiltersForm() }}
    {{-- @if (isset($branch_id)) --}}
    <table class="w-full text-sm text-left pretty reports"   id="report-table">
        <thead style="top:64px;" class="fixed-header">

            <tr>
                <th colspan="3">
                    {{ __('lang.store') }}: ({{ $purchase_invoice_data['store_name'] }})
                </th>
                <th colspan="{{$show_invoice_no == true ? '4' : '3'}}">
                    {{ __('lang.supplier') }}: ({{ $purchase_invoice_data['supplier_name'] }})
                </th>
            </tr>
            <tr>
                <th>{{ __('lang.product_id') }} </th>
                <th>{{ __('lang.product') }}</th>
                <th>{{ __('lang.unit') }}</th>
                <th>{{ __('lang.quantity') }}</th>
                @if ($show_invoice_no == true)
                    <th>{{ __('lang.invoice_no') }}</th>
                @endif
                <th>{{ __('lang.unit_price') }}</th>
                <th>{{ __('lang.total_amount') }}</th>
            </tr>
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
                <tr>
                    <td> {{ $invoice_item?->product_id }} </td>
                    <td> {{ $invoice_item?->product_name }} </td>
                    <td> {{ $invoice_item?->unit_name }} </td>
                    <td> {{ $invoice_item?->quantity }} </td>
                    @if ($show_invoice_no == true)
                        <td>
                            {{ '(' . $invoice_item->purchase_invoice_id . ') ' . $invoice_item->invoice_no }}
                        </td>
                    @endif
                    <td> {{ $unit_price }} </td>
                    <td> {{ $sub_total }} </td>
                </tr>
            @endforeach

            <tr>
                <td colspan="{{$show_invoice_no == true ? '5' : '4'}}"> {{ __('lang.total') }} </td>
                <td> {{ $sum_unit_price }} </td>
                <td> {{ $total_sub_total }} </td>
            </tr>
        </tbody>

    </table>
</x-filament-panels::page>
