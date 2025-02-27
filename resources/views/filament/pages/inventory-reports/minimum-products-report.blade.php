<x-filament::page>
    {{ $this->getTableFiltersForm() }}

    @if (!empty($reportData) && count($reportData) > 0)
        <x-filament-tables::table class="w-full text-sm text-left pretty table-striped">
            <thead>
                <x-filament-tables::row class="header_report">
                    <th colspan="3" class="no_border_right">
                        <h3>{{ __('Inventory Minimum Stock Report') }}</h3>
                    </th>
                    <th colspan="1" class="no_border_left" style="text-align: center; vertical-align: middle; border: none;">
                        <img class="circle-image" src="{{ url('/') . '/storage/logo/default.png' }}" alt="">
                    </th>
                </x-filament-tables::row>
                <x-filament-tables::row>
                    <th>{{ __('lang.product_name') }}</th>
                    <th>{{ __('lang.unit_name') }}</th>
                    <th>{{ __('lang.qty_in_stock') }}</th>
                    <th>{{ __('lang.minimum_quantity') }}</th>
                </x-filament-tables::row>
            </thead>
            <tbody>
                @foreach ($reportData as $data)
                    <x-filament-tables::row>
                        <x-filament-tables::cell>
                            <strong>{{ $data['product_name'] }}</strong>
                        </x-filament-tables::cell>
                        <x-filament-tables::cell>
                            {{ $data['unit_name'] }}
                        </x-filament-tables::cell>
                        <x-filament-tables::cell>
                            <span class="{{ $data['remaining_qty'] < $data['minimum_quantity'] ? 'text-red-500 font-bold' : '' }}">
                                {{ $data['remaining_qty'] }}
                            </span>
                        </x-filament-tables::cell>
                        <x-filament-tables::cell>
                            {{ $data['minimum_quantity'] }}
                        </x-filament-tables::cell>
                    </x-filament-tables::row>
                @endforeach
            </tbody>
        </x-filament-tables::table>
    @else
        <div class="please_select_message_div text-center">
            <h1 class="please_select_message_text">{{ __('No products are currently below minimum stock levels.') }}</h1>
        </div>
    @endif
</x-filament::page>
