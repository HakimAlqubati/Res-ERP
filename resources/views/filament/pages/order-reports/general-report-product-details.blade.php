<x-filament::page>

    {{-- @if (isset($branch_id)) --}}
    {{-- <button wire:click="goBack">back</button> --}}
    <tables::table class="w-full text-sm text-left pretty  ">
        <thead>
            <tables::row class="header_report">
                <th class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }}">
                    <p>{{ __('lang.general_report_of_products') }}</p>
                    <p>({{ $branch }})
                    </p>
                </th>
                <th class="no_border_right_left">
                    <p>{{ __('lang.start_date') . ': ' . $start_date }}</p>
                    <br>
                    <p>{{ __('lang.end_date') . ': ' . $end_date }}</p>
                </th>
                <th class="no_border_right_left">
                    <p>{{ __('lang.category') . ': (' . $category . ')' }}</p>

                </th>
                <th style="text-align: center; vertical-align: middle;"
                    class="{{ app()->getLocale() == 'en' ? 'no_border_left' : 'no_border_right' }}">
                    <img class="circle-image"
                        src="https://w7.pngwing.com/pngs/882/726/png-transparent-chef-cartoon-chef-photography-cooking-fictional-character-thumbnail.png"
                        alt="">
                </th>
            </tables::row>
            <tables::row>
                <th>{{ __('lang.product') }}</th>
                <th>{{ __('lang.unit') }}</th>
                <th>{{ __('lang.quantity') }}</th>
                <th>{{ __('lang.price') }}</th>
            </tables::row>
        </thead>
        <tbody>
            @foreach ($report_data as $data)
                <tables::row>
                    <tables::cell> {{ $data?->product_name }} </tables::cell>
                    <tables::cell> {{ $data?->unit_name }} </tables::cell>
                    <tables::cell> {{ $data?->quantity }} </tables::cell>
                    <tables::cell> {{ $data?->price }} </tables::cell>
                </tables::row>
            @endforeach
            <tables::row>
                <tables::cell colspan="2"> {{ __('lang.total') }} </tables::cell>
                <tables::cell> {{ $total_quantity }} </tables::cell>
                <tables::cell> {{ $total_price }} </tables::cell>
            </tables::row>
        </tbody>

    </tables::table>
</x-filament::page>
