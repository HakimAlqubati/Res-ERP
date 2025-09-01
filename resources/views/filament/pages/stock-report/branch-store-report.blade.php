<x-filament::page>
    {{ $this->getTableFiltersForm() }}
    {{-- @if (isset($branch_id)) --}}
    <table class="w-full text-sm text-left pretty  branch_store_report">
        <thead>

            <tr class="header_report">
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
            </tr>
            <tr>
                <th>{{ __('lang.product_id') }} </th>
                <th>{{ __('lang.product') }}</th>
                <th>{{ __('lang.unit') }}</th>
                <th> {{ __('lang.qty_in_stock') }}</th>
            </tr>
        </thead>
        <tbody>

            @foreach ($branch_store_report_data as $key => $report_item)
                <tr>
                    <td> {{ $report_item?->product_id }} </td>
                    <td> {{ $report_item?->product_name }} </td>
                    <td> {{ $report_item?->unit_name }} </td>
                    <td> {{ $report_item?->total_quantity }} </td>
                </tr>
            @endforeach
            <tr>
                <td colspan="3">{{ __('lang.total_quantity') }}</td>
                <td>{{ $total_quantity }}</td>
            </tr>
        </tbody>

    </table>
</x-filament::page>
