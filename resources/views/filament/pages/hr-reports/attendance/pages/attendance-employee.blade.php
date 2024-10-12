<x-filament-panels::page>
    {{ $this->getTableFiltersForm() }}

    @if (isset($employee_id) && is_numeric($employee_id))
        <x-filament-tables::table class="w-full text-sm text-left pretty  ">
            <thead>
                <x-filament-tables::row class="header_report">
                    <th class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }}">
                        <p>{{ 'Attendance employee report' }}</p>
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
                    <th class="internal_cell">{{ __('From') }}</th>
                    <th class="internal_cell">{{ __('To') }}</th>
                    <th class="internal_cell">{{ __('Attendance') }}</th>
                    <th class="internal_cell">{{ __('Status') }}</th>
                    <th class="internal_cell">{{ __('Departure') }}</th>
                    <th class="internal_cell">{{ __('Status') }}</th>
                    <th class="internal_cell">{{ __('Supposed') }}</th>
                    <th class="internal_cell">{{ __('Total duration hourly') }}</th>
                </x-filament-tables::row>
            </thead>
            <tbody>

                @if ($report_data == 'no_periods')
                    <x-filament-tables::row>
                        <x-filament-tables::cell colspan="100%">
                            {{ 'No any period for this employee' }}
                        </x-filament-tables::cell>
                    </x-filament-tables::row>
                @else
                    @foreach ($report_data as $date => $data)
                        <x-filament-tables::row>
                            <x-filament-tables::cell>
                                {{ $date }}
                            </x-filament-tables::cell>

                            @if (count($data['periods']) > 0)
                                <x-filament-tables::cell colspan="9" style="padding: 0;">
                                    <x-filament-tables::table style="width: 100%">
                                        @foreach ($data['periods'] as $item)
                                            <x-filament-tables::row>
                                                <x-filament-tables::cell
                                                    class="internal_cell">{{ $item['start_at'] }}</x-filament-tables::cell>
                                                <x-filament-tables::cell
                                                    class="internal_cell">{{ $item['end_at'] }}</x-filament-tables::cell>

                                                @if (isset($item['attendances']['checkin']))
                                                    <x-filament-tables::cell class="internal_cell">
                                                        {{ $item['attendances']['checkin'][0]['check_time'] }}
                                                    </x-filament-tables::cell>
                                                    <x-filament-tables::cell class="internal_cell">
                                                        {{ $item['attendances']['checkin'][0]['status'] }}
                                                    </x-filament-tables::cell>
                                                    @if (!isset($item['attendances']['checkout']))
                                                        <x-filament-tables::cell class="internal_cell" colspan="4">
                                                            {{'There is no checkout'}}
                                                        </x-filament-tables::cell>
                                                    @endif
                                                @endif
                                                @if (isset($item['attendances']['checkout']))
                                                    <x-filament-tables::cell class="internal_cell">
                                                        {{ $item['attendances']['checkout']['lastcheckout']['check_time'] }}
                                                    </x-filament-tables::cell>
                                                    <x-filament-tables::cell class="internal_cell">
                                                        {{ $item['attendances']['checkout']['lastcheckout']['status'] }}
                                                    </x-filament-tables::cell>
                                                    <x-filament-tables::cell class="internal_cell">
                                                        {{ $item['attendances']['checkout']['lastcheckout']['supposed_duration_hourly'] }}
                                                    </x-filament-tables::cell>

                                                    <x-filament-tables::cell class="internal_cell">
                                                        <button
                                                            wire:click="showDetails('{{ $date }}', {{ $employee_id }},{{ $item['period_id'] }})"
                                                            class="text-blue-500 hover:underline">

                                                            {{ $item['attendances']['checkout']['lastcheckout']['total_actual_duration_hourly'] }}
                                                        </button>
                                                    </x-filament-tables::cell>
                                                @endif
                                                {{-- @if (isset($item['attendances']['checkin']) && !isset($item['attendances']['checkout']))
                                                <x-filament-tables::cell colspan="4" class="internal_cell">
                                                    {{ 'There is no checkout' }}
                                                </x-filament-tables::cell>
                                            @endif --}}
                                                @if ($item['attendances'] == 'absent')
                                                    <x-filament-tables::cell colspan="6">
                                                        {{ 'Absent' }}
                                                    </x-filament-tables::cell>
                                                @endif
                                            </x-filament-tables::row>
                                        @endforeach
                                    </x-filament-tables::table>
                                </x-filament-tables::cell>
                            @endif
                            @if (count($data['periods']) == 0 && isset($data['holiday']))
                                <x-filament-tables::cell colspan="9">
                                    {{ $data['holiday']['name'] }}
                                </x-filament-tables::cell>
                            @endif
                            @if (count($data['periods']) == 0 && isset($data['leave']))
                                <x-filament-tables::cell colspan="9">
                                    {{ $data['leave']['leave_type_name'] }}
                                </x-filament-tables::cell>
                            @endif


                        </x-filament-tables::row>
                    @endforeach
                @endif
            </tbody>

        </x-filament-tables::table>
    @else
        <div class="please_select_message_div" style="text-align: center;">

            <h1 class="please_select_message_text">{{ __('Please select an Employee') }}</h1>
        </div>
    @endif
    {{-- <center style="font-weight: bolder;color:red">
    {{ 'The report is still under developing_' . 'التقرير لا يزال قيد التطوير' }}</center> --}}
</x-filament-panels::page>
