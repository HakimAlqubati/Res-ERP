<x-filament::page>
    {{ $this->getTableFiltersForm() }}
    {{-- @if (isset($branch_id)) --}}
    <x-filament-tables::table class="w-full text-sm text-left pretty  branch_store_report">
        <thead>

            <x-filament-tables::row class="header_report">
                <th colspan="1" class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }}">
                    <p>{{ __('lang.branch_store_report') }}</p>
                    <p>({{ isset($branch_id) && is_numeric($branch_id) ? \App\Models\Branch::find($branch_id)->name : __('lang.choose_branch') }})
                    </p>
                </th>
                <th colspan="2" class="no_border_right_left">
                    <p>{{ __('lang.start_date') . ': ' . $start_date }}</p>
                    <p>{{ __('lang.end_date') . ': ' . $end_date }}</p>
                </th>
                <th style="text-align: center; vertical-align: middle;"
                    class="{{ app()->getLocale() == 'en' ? 'no_border_left' : 'no_border_right' }}">
                    <img class="circle-image" src="{{ url('/') . '/storage/logo/default.png' }}" alt="">
                </th>
            </x-filament-tables::row>
            <x-filament-tables::row>
                <th>{{ __('lang.product_id') }} </th>
                <th>{{ __('lang.product') }}</th>
                <th>{{ __('lang.unit') }}</th>
                <th> {{ __('lang.qty_in_stock') }}</th>
            </x-filament-tables::row>
        </thead>
        <tbody>

            @foreach ($branch_store_report_data as $key => $report_item)
                <x-filament-tables::row>
                    <x-filament-tables::cell> {{ $report_item?->product_id }} </x-filament-tables::cell>
                    <x-filament-tables::cell> {{ $report_item?->product_name }} </x-filament-tables::cell>
                    <x-filament-tables::cell> {{ $report_item?->unit_name }} </x-filament-tables::cell>
                    <x-filament-tables::cell> {{ $report_item?->total_quantity }} </x-filament-tables::cell>
                </x-filament-tables::row>
            @endforeach
            <x-filament-tables::row>
                <x-filament-tables::cell colspan="3">{{ __('lang.total_quantity') }}</x-filament-tables::cell>
                <x-filament-tables::cell>{{ $total_quantity }}</x-filament-tables::cell>
            </x-filament-tables::row>
        </tbody>

    </x-filament-tables::table>
</x-filament::page>
