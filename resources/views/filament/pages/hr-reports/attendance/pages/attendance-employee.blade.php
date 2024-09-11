<x-filament::page>
    {{ $this->getTableFiltersForm() }}
    @if (isset($employee_id) && is_numeric($employee_id))
        <x-filament-tables::table class="w-full text-sm text-left pretty  ">
            <thead>




                <x-filament-tables::row class="header_report">
                    <th class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }}">
                        <p>{{ 'Attendance Employee report' }}</p>
                        <p>({{ isset($employee_id) && is_numeric($employee_id) ? \App\Models\Employee::find($employee_id)->name : __('lang.choose_branch') }})
                        </p>
                    </th>
                    <th colspan="4" class="no_border_right_left">
                        <p>{{ __('lang.start_date') . ': ' . $start_date }}</p>
                        <br>
                        <p>{{ __('lang.end_date') . ': ' . $end_date }}</p>
                    </th>
                    <th colspan="4" style="text-align: center; vertical-align: middle;"
                        class="{{ app()->getLocale() == 'en' ? 'no_border_left' : 'no_border_right' }}">
                        <img class="circle-image" src="{{ url('/') . '/' . 'storage/workbench.png' }}" alt="">
                    </th>
                </x-filament-tables::row>
                <x-filament-tables::row>
                    <th rowspan="2">{{ __('Date') }}</th>
                    <th colspan="2">{{ __('Shift data') }}</th>

                    <th colspan="2">{{ __('Attendance and Departure data') }}</th>
                    <th colspan="2">{{ __('Count of Hours work') }}</th>
                    <th rowspan="2">{{ __('Early departure (hour)') }}</th>
                    <th rowspan="2">{{ __('Delay time (minute)') }}</th>

                </x-filament-tables::row>
                <x-filament-tables::row>
                    <th>{{ __('From') }}</th>
                    <th>{{ __('To') }}</th>
                    <th>{{ __('Attendance') }}</th>
                    <th>{{ __('Departure') }}</th>
                    <th>{{ __('Supposed') }}</th>
                    <th>{{ __('Actual') }}</th>
                </x-filament-tables::row>
            </thead>
            <tbody>

                @foreach ($report_data as $date => $data)
                    <x-filament-tables::row>

                        <x-filament-tables::cell>
                            {{ $date }}
                        </x-filament-tables::cell>
                        <x-filament-tables::cell>

                        </x-filament-tables::cell>
                        <x-filament-tables::cell>

                        </x-filament-tables::cell>
                        <x-filament-tables::cell>

                        </x-filament-tables::cell>
                        <x-filament-tables::cell>

                        </x-filament-tables::cell>
                        <x-filament-tables::cell>

                        </x-filament-tables::cell>
                        <x-filament-tables::cell>

                        </x-filament-tables::cell>
                        <x-filament-tables::cell>

                        </x-filament-tables::cell>
                        <x-filament-tables::cell>

                        </x-filament-tables::cell>
                    </x-filament-tables::row>
                @endforeach
            </tbody>

        </x-filament-tables::table>
    @else
        <div class="please_select_message_div" style="text-align: center;">

            <h1 class="please_select_message_text">{{ __('Please select an Employee') }}</h1>
        </div>
    @endif
</x-filament::page>
