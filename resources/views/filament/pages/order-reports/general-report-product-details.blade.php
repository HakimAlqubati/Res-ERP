<x-filament-panels::page>

    {{-- @if (isset($branch_id)) --}}
    {{-- <button wire:click="goBack">back</button> --}}
    <x-filament-tables::table class="w-full text-sm text-left pretty  reports" id="report-table">
        <thead class="fixed-header">
            <x-filament-tables::row class="header_report">
                <th class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }}">
                    <p>{{ __('lang.general_report_of_products') }}</p>
                    <p>({{ $branch }})
                    </p>
                </th>
                <th class="no_border_right_left" colspan="2">
                    <p>{{ __('lang.start_date') . ': ' . $start_date }}</p>
                    <br>
                    <p>{{ __('lang.end_date') . ': ' . $end_date }}</p>
                </th>
                <th class="no_border_right_left" colspan="2">
                    <p>{{ __('lang.category') . ': (' . $category . ')' }}</p>

                </th>
                <th style="text-align: center; vertical-align: middle;"
                    class="{{ app()->getLocale() == 'en' ? 'no_border_left' : 'no_border_right' }}">
                    {{-- <img style="display: inline-block;"
                            src="{{ asset('/storage/' . setting('company_logo') . '') }}" alt="Company Logo"
                            class="logo-left"> --}}
                </th>
            </x-filament-tables::row>
            <x-filament-tables::row>
                <th>{{ __('lang.product_code') }}</th>
                <th>{{ __('lang.product') }}</th>
                <th>{{ __('lang.unit') }}</th>
                <th>{{ __('lang.package_size') }}</th>
                <th>{{ __('lang.quantity') }}</th>
                <th>{{ __('lang.price') }}</th>
            </x-filament-tables::row>
        </thead>
        <tbody>
            @foreach ($report_data as $data)
                <x-filament-tables::row>
                    <x-filament-tables::cell title="{{$data->product_id}}" > {{ $data?->product_code }} </x-filament-tables::cell>
                    <x-filament-tables::cell title="{{$data->product_id}}"> {{ $data?->product_name }} </x-filament-tables::cell>
                    <x-filament-tables::cell title="{{$data->unit_id}}"> {{ $data?->unit_name }} </x-filament-tables::cell>
                    <x-filament-tables::cell> {{ $data?->package_size }} </x-filament-tables::cell>
                    <x-filament-tables::cell> {{ $data?->quantity }} </x-filament-tables::cell>
                    <x-filament-tables::cell> {{ $data?->price }} </x-filament-tables::cell>
                </x-filament-tables::row>
            @endforeach
            <x-filament-tables::row>
                <x-filament-tables::cell colspan="4"> {{ __('lang.total') }} </x-filament-tables::cell>
                <x-filament-tables::cell> {{ $total_quantity }} </x-filament-tables::cell>
                <x-filament-tables::cell> {{ $total_price }} </x-filament-tables::cell>
            </x-filament-tables::row>
        </tbody>

    </x-filament-tables::table>
</x-filament-panels::page>
