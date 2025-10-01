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

    @if (!empty($reportData) && count($reportData) > 0 && !is_null($store))
        <table class="w-full text-sm text-left pretty table-striped reports" id="report-table">
            <thead class="fixed-header">
                <tr class="header_report">
                    <th colspan="2" class="no_border_right">
                        <h3>{{ __('Store:( ') . $store . ' )' }}</h3>
                     </th>
                    <th colspan="3" class="no_border_right_left">
                        <h3>{{ __('Inventory Minimum Stock Report') }}</h3>
                    </th>
                    <th colspan="2" class="no_border_left"
                        style="text-align: center; vertical-align: middle; border: none;">
                        <img class="circle-image" src="{{ url('/') . '/storage/logo/default.png' }}" alt="">
                    </th>
                </tr>
                <tr>
                    <th>{{ '' }}</th>
                    <th>{{ __('lang.product_code') }}</th>
                    <th>{{ __('lang.product') }}</th>
                    <th>{{ __('lang.unit_name') }}</th>
                    <th>{{ __('lang.qty_in_stock') }}</th>
                    <th>{{ __('stock.minimum_quantity') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($reportData as $data)
                    <tr>
                        <td>
                            {{ $loop->iteration }}
                        </td>
                        <td title="{{ $data['product_id'] }}">
                            <strong>{{ $data['product_code'] }}</strong>
                        </td>
                        <td>
                            <strong>{{ $data['product_name'] }}</strong>
                        </td>
                        <td>
                            {{ $data['unit_name'] }}
                        </td>
                        <td>
                            <span
                                class="{{ $data['remaining_qty'] < $data['minimum_quantity'] ? 'text-red-500 font-bold' : '' }}">
                                {{ $data['remaining_qty'] }}
                            </span>
                        </td>
                        <td>
                            {{ $data['minimum_quantity'] }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mb-4 text-sm text-gray-700">

            {{-- {{ $reportData->links('vendor.pagination.tailwind') }} --}}

            {{-- links  --}}

        </div>
    @else
        <div class="please_select_message_div text-center">
            <h1 class="please_select_message_text">{{ __('No products are currently below minimum stock levels.') }}
            </h1>
        </div>
    @endif
</x-filament::page>
