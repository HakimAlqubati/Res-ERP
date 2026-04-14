<x-filament::page>
    {{ $this->getTableFiltersForm() }}
    @if (isset($branch_id) && $branch_id !== '')
        @php
            $b_ids = explode(',', $branch_id);
            if (isset($branch_count) && $branch_count > 1) {
                // إذا كان هناك فروع متعددة نعرض كلمة "فروع متعددة" بدلاً من أسمائهم جميعاً
                $branch_heading = \Illuminate\Support\Facades\Lang::has('lang.multiple_branches') 
                    ? __('lang.multiple_branches') 
                    : (app()->getLocale() == 'ar' ? 'فروع متعددة' : 'Multiple Branches');
            } else {
                // إذا كان فرع واحد، نطبع اسم الفرع
                $branch_heading = \App\Models\Branch::whereIn('id', $b_ids)->pluck('name')->implode('، ');
            }
        @endphp
        <table class="w-full text-sm text-left pretty  reports" id="report-table">
            <thead class="fixed-header" style="top:64px;">




                <tr class="header_report">
                    <th colspan="{{ (isset($branch_count) && $branch_count > 1) ? 2 : 1 }}" class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }}">
                        <p>{{ __('lang.general_report_of_products') }}</p>
                        <p>({{ $branch_heading }})</p>
                    </th>
                    <th class="no_border_right_left">
                        <p>{{ __('lang.start_date') . ': ' . $start_date }}</p>
                        <br>
                        <p>{{ __('lang.end_date') . ': ' . $end_date }}</p>
                    </th>
                    <th style="text-align: center; vertical-align: middle;"
                        class="{{ app()->getLocale() == 'en' ? 'no_border_left' : 'no_border_right' }}">
                        <img class="circle-image" src="{{ url('/') . '/' . 'storage/workbench.png' }}" alt="">
                    </th>
                </tr>
                <tr>
                    @if (isset($branch_count) && $branch_count > 1)
                        <th>{{ __('lang.branch') }}</th>
                    @endif
                    <th>{{ __('lang.category') }}</th>

                    <th>{{ __('lang.quantity') }}</th>
                    @if (!isStoreManager())
                        <th>{{ __('lang.price') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody>

                @foreach ($report_data as $data)
                    <tr>
                        @if (isset($branch_count) && $branch_count > 1)
                            <td> {{ $data?->branch_name }} </td>
                        @endif
                        <td>
                            <a target="_blank" href="{{ url($data?->url_report_details) }}">
                                {!! $data?->category !!}</a>
                        </td>
                        <td> {{ $data?->quantity }} </td>
                        @if (!isStoreManager())
                            <td> {{  $data?->amount }}
                            </td>
                        @endif
                    </tr>
                @endforeach
                @if (!isStoreManager())
                    <tr>
                        <td colspan="{{ (isset($branch_count) && $branch_count > 1) ? 2 : 1 }}" style="font-weight:bold; text-align:center;"> 
                            {{ __('lang.total') }} 
                        </td>
                        <td style="font-weight:bold;"> {{ $total_quantity }} </td>
                        <td style="font-weight:bold;"> {{ $total_price }} </td>
                    </tr>
                @endif
            </tbody>

        </table>
    @else
        <div class="please_select_message_div" style="text-align: center;">

            <h1 class="please_select_message_text">{{ __('lang.please_select_branch') }}</h1>
        </div>
    @endif
</x-filament::page>
