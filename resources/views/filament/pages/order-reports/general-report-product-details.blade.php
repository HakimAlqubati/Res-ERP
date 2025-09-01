<x-filament-panels::page>
    <style>

    </style>
    {{-- @if (isset($branch_id)) --}}
    {{-- <button wire:click="goBack">back</button> --}}
    <table class="w-full text-sm text-left pretty  reports" id="report-table">
        <thead class="fixed-header">
            <tr class="header_report">
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
                <th class="no_border_right_left" colspan="3">
                    <p>{{ __('lang.category') . ': (' . $category . ')' }}</p>

                </th>
                <th style="text-align: center; vertical-align: middle;"
                    class="{{ app()->getLocale() == 'en' ? 'no_border_left' : 'no_border_right' }}">
                    {{-- <img style="display: inline-block;"
                            src="{{ asset('/storage/' . setting('company_logo') . '') }}" alt="Company Logo"
                            class="logo-left"> --}}

                    <img style="display: inline-block;" src="{{ asset('/storage/' . setting('company_logo') . '') }}"
                        alt="Company Logo" class="logo-left circle-image">
                </th>
            </tr>
            <tr>
                <th>{{ __('lang.product_code') }}</th>
                <th>{{ __('lang.product') }}</th>
                <th>{{ __('lang.unit') }}</th>
                <th>{{ __('lang.package_size') }}</th>
                <th>{{ __('lang.quantity') }}</th>
                <th>{{ __('lang.unit_price') }}</th>
                <th>{{ __('lang.total_price') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($report_data as $data)
                <tr>
                    <td title="{{ $data->product_id }}"> {{ $data?->product_code }}
                    </td>
                    <td title="{{ $data->product_id }}"> {{ $data?->product_name }}
                    </td>
                    <td title="{{ $data->unit_id }}"> {{ $data?->unit_name }}
                    </td>
                    <td> {{ $data?->package_size }} </td>
                    <td> {{ $data?->quantity }} </td>
                    <td> {{ $data?->unit_price }} </td>
                    <td> {{ $data?->price }} </td>
                </tr>
            @endforeach
            <tr class="sticky-total">
                <td colspan="6" class="font-bold"> {{ __('lang.total') }}
                </td>
                {{-- <td> {{ $total_quantity }} </td> --}}
                <td class="font-bold"> {{ $total_price }} </td>
            </tr>
        </tbody>

    </table>
</x-filament-panels::page>
