<x-filament::page>
    {{ $this->getTableFiltersForm() }}
    @if (isset($branch_id) && is_numeric($branch_id))
        <x-filament-tables::table class="w-full text-sm text-left pretty  reports" id="report-table">
            <thead class="fixed-header" style="top:64px;">




                <x-filament-tables::row class="header_report">
                    <th class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }}">
                        <p>{{ __('lang.general_report_of_products') }}</p>
                        <p>({{ isset($branch_id) && is_numeric($branch_id) ? \App\Models\Branch::find($branch_id)->name : __('lang.choose_branch') }})
                        </p>
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
                </x-filament-tables::row>
                <x-filament-tables::row>
                    <th>{{ __('lang.category') }}</th>

                    <th>{{ __('lang.quantity') }}</th>
                    @if (!isStoreManager())
                        <th>{{ __('lang.price') }}</th>
                    @endif
                </x-filament-tables::row>
            </thead>
            <tbody>

                @foreach ($report_data as $data)
                    <x-filament-tables::row>

                        <x-filament-tables::cell>
                            <a target="_blank" href="{{ url($data?->url_report_details) }}">
                                {{ $data?->category }}</a>
                        </x-filament-tables::cell>
                        <x-filament-tables::cell> {{ $data?->quantity }} </x-filament-tables::cell>
                        @if (!isStoreManager())
                            <x-filament-tables::cell> {{ $data?->amount . ' ' . $data?->symbol }}
                            </x-filament-tables::cell>
                        @endif
                    </x-filament-tables::row>
                @endforeach
                @if (!isStoreManager())
                    <x-filament-tables::row>
                        <x-filament-tables::cell> {{ __('lang.total') }} </x-filament-tables::cell>
                        <x-filament-tables::cell> {{ $total_quantity }} </x-filament-tables::cell>
                        <x-filament-tables::cell> {{ $total_price }} </x-filament-tables::cell>
                    </x-filament-tables::row>
                @endif
            </tbody>

        </x-filament-tables::table>
    @else
        <div class="please_select_message_div" style="text-align: center;">

            <h1 class="please_select_message_text">{{ __('lang.please_select_branch') }}</h1>
        </div>
    @endif
</x-filament::page>
