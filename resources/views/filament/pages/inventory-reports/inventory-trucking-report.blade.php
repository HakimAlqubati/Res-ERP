<x-filament::page>
    {{ $this->getTableFiltersForm() }}

    @if (isset($product) && $product != null)
        <x-filament-tables::table class="w-full text-sm text-left pretty reports table-striped">
            <thead>
                <x-filament-tables::row class="header_report">
                    <th colspan="2">{{ $product->name }}</th>
                    <th colspan="3" class="no_border_right_left" style="text-align: center;">
                        <h3>({{ 'Inventory Trucking' }})</h3>
                    </th>
                    <th colspan="2" style="text-align: center;">
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
                        <x-filament-tables::cell> {{ $data->movement_date }} </x-filament-tables::cell>
                        <x-filament-tables::cell>
                            {{ $data->formatted_transactionable_type }}
                        </x-filament-tables::cell>
                        <x-filament-tables::cell> {{ $data->transactionable_id }} </x-filament-tables::cell>
                        <x-filament-tables::cell>
                            {{ $data->unit_id ? \App\Models\Unit::find($data->unit_id)->name : '' }}
                        </x-filament-tables::cell>
                        <x-filament-tables::cell> {{ $data->quantity }} </x-filament-tables::cell>
                        <x-filament-tables::cell colspan="2"> {{ $data->notes }} </x-filament-tables::cell>
                    </x-filament-tables::row>
                @endforeach
            </tbody>
        </x-filament-tables::table>

        {{-- Pagination Links --}}
        <div class="mb-4 text-sm text-gray-700">
            {{ $reportData->links() }}

            {{-- Showing {{ $reportData->firstItem() }} to {{ $reportData->lastItem() }} of {{ $reportData->total() }} results --}}
        </div>
    @else
        <div class="please_select_message_div" style="text-align: center;">
            <h1 class="please_select_message_text">{{ 'Please Select a Product' }}</h1>
        </div>
    @endif
</x-filament::page>
