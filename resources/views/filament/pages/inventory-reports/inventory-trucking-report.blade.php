<x-filament::page>
    {{ $this->getTableFiltersForm() }}
    @if (isset($product) && $product != null)
        <x-filament-tables::table class="w-full text-sm text-left pretty  ">
            <thead>

                <x-filament-tables::row class="header_report">
                    <th colspan="2" class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }}">
                        {{ $product->name }}
                    </th>
                    <th colspan="3" class="no_border_right_left" style="text-align: center; vertical-align: middle;">
                        <h3>({{ 'Inventory Trucking' }})</h3>
                    </th>
                    <th colspan="2" style="text-align: center; vertical-align: middle;"
                        class="{{ app()->getLocale() == 'en' ? 'no_border_left' : 'no_border_right' }}">
                        <img class="circle-image" src="{{ url('/') . '/storage/logo/default.png' }}" alt="">
                    </th>
                </x-filament-tables::row>
                <x-filament-tables::row>
                    <th>{{ 'Date' }}</th>
                    <th>{{ 'Transaction Type' }}</th>
                    <th>{{ 'Transaction ID' }}</th>
                    <th>{{ 'Unit' }}</th>
                    <th>{{ 'Qty' }}</th>
                    <th colspan="2">{{ 'Notes' }}</th>

                </x-filament-tables::row>
            </thead>
            <tbody>


                @foreach ($reportData as $data)
                    <x-filament-tables::row>


                        <x-filament-tables::cell>
                            {{ $data['date'] }}
                        </x-filament-tables::cell>
                        <x-filament-tables::cell>
                            {{ $data['type'] }}
                        </x-filament-tables::cell>
                        <x-filament-tables::cell>
                            {{ $data['reference_id'] }}
                        </x-filament-tables::cell>

                        <x-filament-tables::cell>
                            {{ $data['unit_name'] }}
                        </x-filament-tables::cell>
                        <x-filament-tables::cell>
                            {{ $data['quantity'] }}
                        </x-filament-tables::cell>
                        <x-filament-tables::cell colspan="2">
                            {{ $data['notes'] }}
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
