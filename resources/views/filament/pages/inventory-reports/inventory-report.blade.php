<x-filament::page>
    {{ $this->getTableFiltersForm() }}
    @if (isset($product) && $product != null)
        <x-filament-tables::table class="w-full text-sm text-left pretty table-striped">
            <thead>

                <x-filament-tables::row class="header_report">
                    <th colspan="1" class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }}">
                        {{ $product->name }}
                    </th>
                    <th colspan="2" class="no_border_right_left" style="text-align: center; vertical-align: middle;border:none;">
                        <h3>({{ 'Inventory Movement Report' }})</h3>
                    </th>
                    <th colspan="1" style="text-align: center; vertical-align: middle;border:none;"
                        class="{{ app()->getLocale() == 'en' ? 'no_border_left' : 'no_border_right' }}">
                        <img class="circle-image logo-left" src="{{ url('/') . '/storage/logo/default.png' }}" alt="">
                    </th>
                </x-filament-tables::row>
                <x-filament-tables::row>
                    <th>{{ 'unit Id' }}</th>
                    <th colspan="2">{{ __('lang.unit') }}</th>
                    <th>{{ __('lang.qty_in_stock') }}</th>
                </x-filament-tables::row>
            </thead>
            <tbody>


                @foreach ($reportData as $data)
                    <x-filament-tables::row>


                        <x-filament-tables::cell>
                            {{ $data['unit_id'] }}
                        </x-filament-tables::cell>

                        <x-filament-tables::cell colspan="2">
                            <strong>{{ $data['unit_name'] }}</strong>
                        </x-filament-tables::cell>
                        <x-filament-tables::cell>
                            <strong>{{ $data['remaining_qty'] }}</strong>
                        </x-filament-tables::cell>

                    </x-filament-tables::row>
                @endforeach

            </tbody>

        </x-filament-tables::table>
    @else
        <div class="please_select_message_div" style="text-align: center;">

            <h1 class="please_select_message_text">{{ 'Please Select a Product' }}</h1>
        </div>
    @endif
</x-filament::page>
