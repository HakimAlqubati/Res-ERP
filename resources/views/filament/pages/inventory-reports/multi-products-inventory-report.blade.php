<x-filament::page>
    {{ $this->getTableFiltersForm() }}

    @if (!empty($reportData))
        <x-filament-tables::table class="w-full text-sm text-left pretty reports table-striped">
            <thead>
                <x-filament-tables::row class="header_report">
                    <th colspan="5" style="text-align: center; vertical-align: middle;">
                        <h3>Inventory Movement Report</h3>
                    </th>
                </x-filament-tables::row>
                <x-filament-tables::row>
                    <th>Product Name</th>
                    <th>Unit ID</th>
                    <th>Unit Name</th>
                    <th>Package Size</th>
                    <th>Quantity in Stock</th>
                </x-filament-tables::row>
            </thead>
            <tbody>
                @foreach ($reportData as $productReport)
                    @foreach ($productReport as $data)
                        <x-filament-tables::row>
                            <x-filament-tables::cell>
                                <strong>{{ $data['product_name'] }}</strong>
                            </x-filament-tables::cell>
                            <x-filament-tables::cell>
                                {{ $data['unit_id'] }}
                            </x-filament-tables::cell>
                            <x-filament-tables::cell>
                                {{ $data['unit_name'] }}
                            </x-filament-tables::cell>
                            <x-filament-tables::cell>
                                {{ $data['package_size'] }}
                            </x-filament-tables::cell>
                            <x-filament-tables::cell>
                                <strong>{{ $data['remaining_qty'] }}</strong>
                            </x-filament-tables::cell>
                        </x-filament-tables::row>
                    @endforeach
                @endforeach
            </tbody>
        </x-filament-tables::table>
    @else
        <div class="please_select_message_div" style="text-align: center;">
            <h1 class="please_select_message_text">No inventory data available.</h1>
        </div>
    @endif
</x-filament::page>
