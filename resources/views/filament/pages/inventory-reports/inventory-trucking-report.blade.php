<x-filament::page>
    {{ $this->getTableFiltersForm() }}

    @if (isset($product) && $product != null)
        <x-filament-tables::table class="w-full text-sm text-left pretty reports table-striped" id="report-table">
            <thead class="fixed-header">
                <x-filament-tables::row class="header_report">

                    <th colspan="2" title="{{ $product->id }}">{{ $product->name }}</th>
                    <th colspan="4" class="no_border_right_left" style="text-align: center;">
                        <h3>({{ 'Inventory Trucking' }})</h3>
                    </th>
                    <th colspan="2" style="text-align: center;">
                        <img class="circle-image" src="{{ asset('/storage/' . setting('company_logo') . '') }}"
                            alt="">
                    </th>
                </x-filament-tables::row>
                <x-filament-tables::row>
                    <th>{{ 'Date' }}</th>
                    <th>{{ 'Transaction Type' }}</th>
                    <th>{{ 'Transaction ID' }}</th>
                    <th>{{ 'Unit' }}</th>
                    <th>{{ 'Qty per Pack' }}</th>
                    <th>{{ 'Qty' }}</th>
                    <th>{{ 'Store' }}</th>
                    <th colspan="2">{{ 'Notes' }}</th>
                </x-filament-tables::row>
            </thead>
            <tbody>
                @php
                    $totalQty = 0;
                @endphp
                @foreach ($reportData as $data)
                    <x-filament-tables::row>
                        <x-filament-tables::cell> {{ $data->movement_date }} </x-filament-tables::cell>
                        <x-filament-tables::cell>
                            {{ $data->formatted_transactionable_type }}
                        </x-filament-tables::cell>
                        <x-filament-tables::cell> {{ $data->transactionable_id }} </x-filament-tables::cell>
                        <x-filament-tables::cell title="{{ $data->unit_id }}">
                            {{ $data->unit_id ? \App\Models\Unit::find($data->unit_id)->name : '' }}
                        </x-filament-tables::cell>

                        <x-filament-tables::cell> {{ $data->package_size }} </x-filament-tables::cell>
                        <x-filament-tables::cell> {{ $data->quantity }} </x-filament-tables::cell>
                        <x-filament-tables::cell> {{ $data->store->name ?? '' }} </x-filament-tables::cell>
                        <x-filament-tables::cell colspan="2"> {{ $data->notes }} </x-filament-tables::cell>
                    </x-filament-tables::row>
                    @php
                        $totalQty += $data->quantity;
                    @endphp
                @endforeach
            </tbody>
            @if ($unitId && !is_null($unitId) && isset($movementType) && !is_null($movementType))
                <tfoot>
                    <x-filament-tables::row class="font-bold bg-gray-100">
                        <x-filament-tables::cell colspan="5" class="text-right">Total
                            Quantity:</x-filament-tables::cell>
                        <x-filament-tables::cell>{{ $totalQty }}</x-filament-tables::cell>
                        <x-filament-tables::cell colspan="2"></x-filament-tables::cell>
                    </x-filament-tables::row>
                </tfoot>
            @endif
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
