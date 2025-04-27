<x-filament-panels::page>
    {{ $this->getTableFiltersForm() }}
    {{-- @if (isset($branch_id)) --}}
    <x-filament-tables::table class="w-full text-sm text-left pretty reports"   id="report-table">
        <thead style="top:64px;" class="fixed-header">

            <x-filament-tables::row>
                <th colspan="3">
                    {{ __('lang.store') }}: ({{ $purchase_invoice_data['store_name'] }})
                </th>
                <th colspan="{{$show_invoice_no == true ? '4' : '3'}}">
                    {{ __('lang.supplier') }}: ({{ $purchase_invoice_data['supplier_name'] }})
                </th>
            </x-filament-tables::row>
            <x-filament-tables::row>
                <th>{{ __('lang.product_code') }} </th>
                <th>{{ __('lang.product') }}</th>
                <th>{{ __('lang.unit') }}</th>
                <th>{{ __('lang.quantity') }}</th>
                @if ($show_invoice_no == true)
                    <th>{{ __('lang.invoice_no') }}</th>
                @endif
                <th>{{ __('lang.unit_price') }}</th>
                <th>{{ __('lang.total_amount') }}</th>
            </x-filament-tables::row>
        </thead>
        <tbody>
            @php
                $total_sub_total = 0;
                $sum_unit_price = 0;
            @endphp
            @foreach ($purchase_invoice_data['results'] as $invoice_item)
                @php
                    $unit_price = $invoice_item->unit_price;
                    $sub_total = $invoice_item->unit_price * $invoice_item->quantity;
        
                    $total_sub_total += $sub_total;
                    $sum_unit_price += $unit_price;
                @endphp
                <x-filament-tables::row>
                    <x-filament-tables::cell> {{ $invoice_item->product_code }} </x-filament-tables::cell>
                    <x-filament-tables::cell> {{ $invoice_item->product_name }} </x-filament-tables::cell>
                    <x-filament-tables::cell> {{ $invoice_item->unit_name }} </x-filament-tables::cell>
                    <x-filament-tables::cell> {{ $invoice_item->quantity }} </x-filament-tables::cell>
                    @if ($show_invoice_no == true)
                        <x-filament-tables::cell>
                            {{ '(' . $invoice_item->purchase_invoice_id . ') ' . $invoice_item->invoice_no }}
                        </x-filament-tables::cell>
                    @endif
                    <x-filament-tables::cell> {{ $unit_price }} </x-filament-tables::cell>
                    <x-filament-tables::cell> {{ $sub_total }} </x-filament-tables::cell>
                </x-filament-tables::row>
            @endforeach
        
            <x-filament-tables::row>
                <x-filament-tables::cell colspan="{{ $show_invoice_no ? '5' : '4' }}"> {{ __('lang.total') }} </x-filament-tables::cell>
                <x-filament-tables::cell> {{ $sum_unit_price }} </x-filament-tables::cell>
                <x-filament-tables::cell> {{ $total_sub_total }} </x-filament-tables::cell>
            </x-filament-tables::row>
        </tbody>
        
        {{-- 🔹 Add Pagination Links --}}
        <tr>
            <td colspan="100%">
                {{ $purchase_invoice_data['results']->links() }}
            </td>
        </tr>
        

    </x-filament-tables::table>
</x-filament-panels::page>
