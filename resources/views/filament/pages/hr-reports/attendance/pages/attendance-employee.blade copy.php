<x-filament::page>
    {{ $this->getTableFiltersForm() }}
    @if (isset($employee_id) && is_numeric($employee_id))
        <x-filament-tables::table class="w-full text-sm text-left pretty  ">
            <thead>




                <x-filament-tables::row class="header_report">
                    <th class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }}">
                        <p>{{ __('lang.general_report_of_products') }}</p>
                        <p>({{ isset($employee_id) && is_numeric($employee_id) ? \App\Models\Employee::find($employee_id)->name : __('lang.choose_branch') }})
                        </p>
                    </th>
                    <th class="no_border_right_left">
                        <p>{{ __('lang.start_date') . ': ' . $start_date }}</p>
                        <br>
                        <p>{{ __('lang.end_date') . ': ' . $end_date }}</p>
                    </th>
                    <th style="text-align: center; vertical-align: middle;"
                        class="{{ app()->getLocale() == 'en' ? 'no_border_left' : 'no_border_right' }}">
                        <img class="circle-image"
                            src="https://w7.pngwing.com/pngs/882/726/png-transparent-chef-cartoon-chef-photography-cooking-fictional-character-thumbnail.png"
                            alt="">
                    </th>
                </x-filament-tables::row>
                <x-filament-tables::row>
                    <th>{{ __('Employee') }}</th>

                    <th>{{ __('Period One') }}</th>
                    <th>{{ __('lang.price') }}</th>
                </x-filament-tables::row>
            </thead>
            <tbody>

                @foreach ($report_data as $data)
                    
                    <x-filament-tables::row>

                        <x-filament-tables::cell>
                            <a target="_blank" href="{{ url($data?->url_report_details) }}"> {{ $data?->category }}</a>
                        </x-filament-tables::cell>
                        <x-filament-tables::cell> {{ $data?->quantity }} </x-filament-tables::cell>
                        <x-filament-tables::cell> {{ $data?->amount . ' ' . $data?->symbol }} </x-filament-tables::cell>
                    </x-filament-tables::row>
                @endforeach
                <x-filament-tables::row>
                    <x-filament-tables::cell> {{ __('lang.total') }} </x-filament-tables::cell>
                    <x-filament-tables::cell> {{ $total_quantity }} </x-filament-tables::cell>
                    <x-filament-tables::cell> {{ $total_price }} </x-filament-tables::cell>
                </x-filament-tables::row>
            </tbody>

        </x-filament-tables::table>
    @else
        <div class="please_select_message_div" style="text-align: center;">

            <h1 class="please_select_message_text">{{ __('Please select an Employee') }}</h1>
        </div>
    @endif
</x-filament::page>
