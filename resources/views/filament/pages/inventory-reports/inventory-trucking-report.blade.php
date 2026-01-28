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
                <th colspan="4" class="no_border_right_left" style="text-align: center;">
                    <h3>({{ 'Inventory Tracking' }})</h3>
                </th>
                <th colspan="2" style="text-align: center;"
                    class="{{ app()->getLocale() == 'ar' ? 'no_border_right' : 'no_border_left' }}">
                    <img class="circle-image" src="{{ asset('/storage/' . setting('company_logo') . '') }}"
                        alt="">
                </th>
            </tr>
            <tr>
                <th>{{ 'Date' }}</th>
                <th>{{ 'Batch Number' }}</th>
                <th>{{ 'Transaction Type' }}</th>
                <th>{{ 'Transaction ID' }}</th>
                <th>{{ 'Unit' }}</th>
                <th>{{ 'Qty per Pack' }}</th>
                <th>{{ 'Qty' }}</th>
                <th>{{ 'Store' }}</th>
                <th colspan="2">{{ 'Notes' }}</th>
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
                <td> {{ $data->transactionable_id }} </td>
                <td title="{{ $data->unit_id }}">
                    {{ $data->unit_id ? \App\Models\Unit::find($data->unit_id)->name : '' }}
                </td>

                <td> {{ $data->package_size }} </td>
                <td> {{ $data->quantity }} </td>
                <td> {{ $data->store->name ?? '' }} </td>
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
                <td colspan="2"></td>
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