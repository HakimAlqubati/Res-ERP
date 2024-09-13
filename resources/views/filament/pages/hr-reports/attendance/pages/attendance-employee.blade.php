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

                    <th colspan="4">{{ __('Attendance and Departure data') }}</th>
                    <th colspan="2">{{ __('Count of Hours work') }}</th>
                    {{-- <th rowspan="2">{{ __('Early departure (hour)') }}</th>
                    <th rowspan="2">{{ __('Delay time (minute)') }}</th> --}}

                </x-filament-tables::row>
                <x-filament-tables::row>
                    <th>{{ __('From') }}</th>
                    <th>{{ __('To') }}</th>
                    <th>{{ __('Attendance') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Departure') }}</th>
                    <th>{{ __('Status') }}</th>
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

                        @if (isset($data[0]->check_type) && count($data) == 1 && $data[0]->check_type == 'Absent')
                            <x-filament-tables::cell style="background:red;" colspan="8">
                                <p>{{ 'Absent' }}</p>
                            </x-filament-tables::cell>
                        @endif
                        @if (isset($data[0]->holiday_name) && count($data) == 1 && $data[0]->check_type == 'Holiday')
                            <x-filament-tables::cell style="background:green;" colspan="8">
                                <p>{{ $data[0]->holiday_name }}</p>
                            </x-filament-tables::cell>
                        @endif
                        {{-- @if (isset($data[0]->check_type) && count($data) == 1 && $data[0]->check_type == 'Weekend')
                            <x-filament-tables::cell style="background:green;" colspan="8">
                                <p>{{ $data[0]->Weekend }}</p>
                            </x-filament-tables::cell>
                        @endif   --}}
                        @if (count($data) >= 2)
                            <x-filament-tables::cell>

                            </x-filament-tables::cell>

                            <x-filament-tables::cell>

                            </x-filament-tables::cell>

                            <x-filament-tables::cell>
                                @if (count($data) >= 2)
                                    @if (isset($data[0]->check_type) && $data[0]->check_type == \App\Models\Attendance::CHECKTYPE_CHECKIN)
                                        {{ $data[0]->check_time }}
                                    @endif
                                @endif
                            </x-filament-tables::cell>

                            <x-filament-tables::cell>
                                @if (count($data) >= 2)
                                    @if (isset($data[0]->check_type) && $data[0]->check_type == \App\Models\Attendance::CHECKTYPE_CHECKIN)
                                        {{ $data[0]->status }}
                                    @endif
                                @endif
                            </x-filament-tables::cell>


                            <x-filament-tables::cell>
                                @if (count($data) >= 2)
                                    @if (isset($data[1]->check_type) && $data[1]->check_type == \App\Models\Attendance::CHECKTYPE_CHECKOUT)
                                        {{ $data[1]->check_time }}
                                    @endif
                                @endif

                            </x-filament-tables::cell>

                            <x-filament-tables::cell>
                                @if (count($data) >= 2)
                                    @if (isset($data[1]->check_type) && $data[1]->check_type == \App\Models\Attendance::CHECKTYPE_CHECKOUT)
                                        {{ $data[1]->status }}
                                    @endif
                                @endif
                            </x-filament-tables::cell>



                            <x-filament-tables::cell>
                                @if (count($data) >= 2)
                                    @if (isset($data[1]->check_type) && $data[1]->check_type == \App\Models\Attendance::CHECKTYPE_CHECKOUT)
                                        {{ $data[1]->supposed_duration_hourly }}
                                    @endif
                                @endif
                            </x-filament-tables::cell>

                            <x-filament-tables::cell>
                                @if (count($data) >= 2)
                                    @if (isset($data[1]->check_type) && $data[1]->check_type == \App\Models\Attendance::CHECKTYPE_CHECKOUT)
                                        {{ $data[1]->actual_duration_hourly }}
                                    @endif
                                @endif
                            </x-filament-tables::cell>



                            {{-- <x-filament-tables::cell>

                        </x-filament-tables::cell>
                        <x-filament-tables::cell>

                        </x-filament-tables::cell> --}}
                        @endif
                    </x-filament-tables::row>
                @endforeach
            </tbody>

        </x-filament-tables::table>
    @else
        <div class="please_select_message_div" style="text-align: center;">

            <h1 class="please_select_message_text">{{ __('Please select an Employee') }}</h1>
        </div>
    @endif
    <center style="font-weight: bolder;color:red">  {{'The report is still under developing_'.'التقرير لا يزال قيد التطوير'}}</center>
</x-filament::page>
