<x-filament::page>
    {{ $this->getTableFiltersForm() }}
    {{-- @if (isset($branch_id)) --}}
    <tables::table class="w-full text-sm text-left pretty displayschedule">
        <thead>
            <tables::row>
                <th>{{ __('lang.product_id') }} </th>
                <th>{{ __('lang.product') }}</th>
                <th>{{ __('lang.unit') }}</th>
                <th>{{ __('lang.quantity') }}</th>
                <th>{{ __('lang.unit_price') }}</th>
                <th>{{ __('lang.total_amount') }}</th>
            </tables::row>
        </thead>
        <tbody>
            @foreach ($stock_data as $key => $data)
                <tables::row>
                    <tables::cell> </tables::cell>
                    <tables::cell> </tables::cell>
                    <tables::cell> </tables::cell>
                    <tables::cell> </tables::cell>
                    <tables::cell> </tables::cell>
                    <tables::cell> </tables::cell>
                </tables::row>
            @endforeach

            <tables::row>
                <tables::cell></tables::cell>
                <tables::cell> </tables::cell>
                <tables::cell> </tables::cell>
                <tables::cell> </tables::cell>
                <tables::cell> </tables::cell>
                <tables::cell> </tables::cell>
            </tables::row>
        </tbody>

    </tables::table>
</x-filament::page>
