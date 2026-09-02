<x-filament::page>
    <style>
        table {
            /* border-collapse: collapse; */
            width: 100%;
            border-collapse: inherit;
            border-spacing: initial;
        }

        .fi-tabs {
            display: none !important;
        }
    </style>
    {{ $this->getTableFiltersForm() }}

    @if (isset($product) && $product != null)
    <table class="w-full text-sm text-left pretty reports table-striped" id="report-table">
        <thead class="fixed-header">
            <tr class="header_report">

                <th colspan="3" title="{{ $product->id }}"
                    class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }}">
                    {{ $product->name }}
                </th>
                <th colspan="{{ (isset($showGradiants) && $showGradiants) ? 5 : 4 }}" class="no_border_right_left" style="text-align: center;">
                    <h3>({{ 'Inventory Tracking' }})</h3>
                </th>
                <th colspan="3" style="text-align: center;"
                    class="{{ app()->getLocale() == 'ar' ? 'no_border_right' : 'no_border_left' }}">
                    <img class="circle-image" src="{{ asset('/storage/' . setting('company_logo') . '') }}"
                        alt="">
                </th>
            </tr>
            <tr>
                <th>{{ 'Date' }}</th>
                <th>{{ 'Batch Number' }}</th>
                <th>{{ 'Transaction Type' }}</th>
                <th>{{ 'Movement Type' }}</th>
                <th>{{ 'Transaction ID' }}</th>
                <th>{{ 'Unit' }}</th>
                <th>{{ 'Qty per Pack' }}</th>
                <th>{{ 'Qty' }}</th>
                <th>{{ 'Store' }}</th>
                @if (isset($showGradiants) && $showGradiants)
                <th>{{ 'Gradiants' }}</th>
                @endif
                <th colspan="3">{{ 'Notes' }}</th>
            </tr>
        </thead>
        <tbody>
            @php
            $totalQty = 0;
            @endphp 
            @foreach ($reportData as $data)
            <tr>
                <td> {{ $data->movement_date }} </td>
                <td> {{ $data->batch_number }} </td>
                <td>
                    {{ $data->formatted_transactionable_type }}
                </td>
                <td>
                    {{ $data->movement_type }}
                </td>
                <td> {{ $data->transactionable_id }} </td>
                <td title="{{ $data->unit_id }}">
                    {{ $data->unit_id ? \App\Models\Unit::find($data->unit_id)->name : '' }}
                </td>

                <td> {{ $data->package_size }} </td>
                <td> {{ $data->quantity }} </td>
                <td> {{ $data->store->name ?? '' }} </td>
                @if (isset($showGradiants) && $showGradiants)
                <td style="vertical-align: top; text-align: {{ app()->getLocale() == 'ar' ? 'right' : 'left' }};">
                    @if ($data->movement_type == 'in' && ($data->transactionable_type == 'App\Models\StockSupplyOrder' || $data->transactionable_type == \App\Models\StockSupplyOrder::class))
                        @php
                            $excludedCodes = ['11076', '06002'];
                            $filteredItems = $product && $product->productItems 
                                ? $product->productItems->filter(function($item) use ($excludedCodes) {
                                    $code = (string) ($item->product?->code ?? '');
                                    $id = (string) ($item->product_id ?? '');
                                    return !in_array($code, $excludedCodes, true) && !in_array($id, $excludedCodes, true);
                                })
                                : collect();
                        @endphp
                        @if ($filteredItems->isNotEmpty())
                            <div style="display: flex; flex-direction: column; gap: 3px; font-size: 11px;">
                                @foreach ($filteredItems as $item)
                                    @php
                                        $itemCode = $item->product?->code ?? '';
                                        $itemName = $item->product?->name ?? '';
                                        $unitName = $item->unit?->name ?? '';
                                        $recipeQty = (float) $item->quantity + 0;
                                        $supplyQty = (float) $data->quantity + 0;
                                        $totalQty = round($recipeQty * $supplyQty, 4) + 0;
                                    @endphp
                                    <div style="white-space: nowrap; line-height: 1.3;">
                                        <strong>{{ $itemCode }}</strong> - <bdi>{{ $itemName }}</bdi> - <span dir="ltr"><strong>{{ $recipeQty }} {{ $unitName }}</strong> * <strong>{{ $supplyQty }}</strong> = <strong>{{ $totalQty }} {{ $unitName }}</strong></span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            -
                        @endif
                    @else
                        -
                    @endif
                </td>
                @endif
                <td colspan="2"> {{ $data->notes }} </td>
            </tr>
            @php
            $totalQty += $data->quantity;
            @endphp
            @endforeach
        </tbody>
        @if ($unitId && !is_null($unitId) && isset($movementType) && !is_null($movementType))
        <tfoot>
            <tr class="font-bold bg-gray-100">
                <td colspan="6" class="text-right">Total
                    Quantity:</td>
                <td>{{ $totalQty }}</td>
                <td colspan="{{ (isset($showGradiants) && $showGradiants) ? 3 : 2 }}"></td>
            </tr>
        </tfoot>
        @endif
    </table>

    {{-- Pagination Links --}}
    <div class="mt-4">
        <div class="paginator_container">
            @if (isset($reportData) && $reportData instanceof \Illuminate\Pagination\LengthAwarePaginator)
            {{ $reportData->links() }}
            @endif
        </div>


        <x-per-page-selector />
    </div>
    @else
    <div class="please_select_message_div" style="text-align: center;">
        <h1 class="please_select_message_text">{{ 'Please Select a Product' }}</h1>
    </div>
    @endif
</x-filament::page>