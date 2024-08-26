<x-filament::page>
    {{ $this->getTableFiltersForm() }}
    {{-- @if (isset($product_id) && is_numeric($product_id)) --}}
        <x-filament-tables::table class="w-full text-sm text-left pretty  ">
            <thead>




                <x-filament-tables::row class="header_report">
                    <th class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }}">
                        <p>{{ __('lang.report_product_quantities') }}</p>
                        <p>({{ isset($product_id) && is_numeric($product_id) ? \App\Models\Product::find($product_id)->name : __('lang.choose_product') }})
                        </p>
                    </th>
                    <th colspan="2" class="no_border_right_left">
                        <p>{{ __('lang.start_date') . ': ' . $start_date }}</p>
                        <br>
                        <p>{{ __('lang.end_date') . ': ' . $end_date }}</p>
                    </th>
                    <th style="text-align: center; vertical-align: middle;"
                        class="{{ app()->getLocale() == 'en' ? 'no_border_left' : 'no_border_right' }}">
                        <img class="circle-image"
                            src="https://w7.pngwing.com/pngs/882/726/png-transparent-chef-cartoon-chef-photography-cooking-fictional-character-thumbnail.png"
                            alt="">
                    </th>
                </x-filament-tables::row>
                <x-filament-tables::row>
                    <th>{{ __('lang.branch') }}</th>
                    <th>{{ __('lang.unit') }}</th>
                    <th>{{ __('lang.quantity') }}</th>
                    <th>{{ __('lang.price') }}</th>
                </x-filament-tables::row>
            </thead>
            <tbody>
                @foreach ($report_data as $data)
                    <x-filament-tables::row>

                        <x-filament-tables::cell> {{ $data?->branch }} </x-filament-tables::cell>
                        <x-filament-tables::cell> {{ $data?->unit }} </x-filament-tables::cell>
                        <x-filament-tables::cell> {{ $data?->quantity }} </x-filament-tables::cell>
                        <x-filament-tables::cell> {{ $data?->price }} </x-filament-tables::cell>


                    </x-filament-tables::row>
                @endforeach

                <x-filament-tables::row>
                    <x-filament-tables::cell colspan="2"> {{ __('lang.total') }} </x-filament-tables::cell>

                    <x-filament-tables::cell> {{ $total_quantity }} </x-filament-tables::cell>
                    <x-filament-tables::cell> {{ $total_price }} </x-filament-tables::cell>
                </x-filament-tables::row>
            </tbody>

        </x-filament-tables::table>
    {{-- @else
        <div class="please_select_message_div" style="text-align: center;">

            <h1 class="please_select_message_text">{{ __('lang.please_select_product') }}</h1>
        </div>
    @endif --}}
</x-filament::page>
