<x-filament::page>
    <style>
        .fi-tabs {
            display: none !important;
        }
    </style>
    {{ $this->getTableFiltersForm() }}
    @if (isset($product) && $product != null)
        <table class="w-full text-sm text-left pretty table-striped">
            <thead>

                <tr class="header_report">
                    <th colspan="1" class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }}">
                        {{ $product->name }}
                    </th>
                    <th colspan="2" class="no_border_right_left"
                        style="text-align: center; vertical-align: middle;border:none;">
                        <h3>({{ 'Inventory Movement Report' }})</h3>
                    </th>
                    <th colspan="1" style="text-align: center; vertical-align: middle;border:none;"
                        class="{{ app()->getLocale() == 'en' ? 'no_border_left' : 'no_border_right' }}">
                        <img class="circle-image logo-left" src="{{ url('/') . '/storage/logo/default.png' }}"
                            alt="">
                    </th>
                </tr>
                <tr>
                    <th>{{ 'unit Id' }}</th>
                    <th colspan="2">{{ __('lang.unit') }}</th>
                    <th>{{ __('lang.qty_in_stock') }}</th>
                </tr>
            </thead>
            <tbody>


                @foreach ($reportData as $data)
                    <tr>


                        <td>
                            {{ $data['unit_id'] }}
                        </td>

                        <td colspan="2">
                            <strong>{{ $data['unit_name'] }}</strong>
                        </td>
                        <td>
                            <strong>{{ $data['remaining_qty'] }}</strong>
                        </td>

                    </tr>
                @endforeach

            </tbody>

        </table>
    @else
        <div class="please_select_message_div" style="text-align: center;">

            <h1 class="please_select_message_text">{{ 'Please Select a Product' }}</h1>
        </div>
    @endif
</x-filament::page>
