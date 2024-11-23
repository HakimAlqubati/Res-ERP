<x-filament-panels::page>
    {{ $this->getTableFiltersForm() }}

    <style>
        table {
            /* border-collapse: collapse; */
            width: 100%;
            border-collapse: inherit;
            border-spacing: initial;
        }
    </style>

    {{-- @if (isset($branch_id) && is_numeric($branch_id)) --}}
    <x-filament-tables::table class="w-full text-sm text-left pretty  reports" id="report-table">
        <thead>
            <x-filament-tables::row class="header_report">
                <th class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }}">
                    <p>{{ 'Attendance employees report' }}</p>

                    <p> Branch:
                        {{ $branch_id == '' ? ' ( All Branches ) ' : \App\Models\Branch::find($branch_id)?->name }}
                    </p>
                </th>
                <th colspan="4" class="no_border_right_left">
                    {{-- <p>{{ 'Date' . ': ' . $date }}</p> --}}

                </th>
                <th colspan="4" style="text-align: center; vertical-align: middle; padding:12px;"
                    class="{{ app()->getLocale() == 'en' ? 'no_border_left' : 'no_border_right' }}">
                    <img class="circle-image" src="{{ url('/') . '/' . 'storage/workbench.png' }}" alt="">
                </th>
            </x-filament-tables::row>

            <x-filament-tables::row>
                <th rowspan="2">{{ __('Employee') }}</th>
                <th colspan="2">{{ __('Shift data') }}</th>

                <th colspan="4">{{ __('Check-in and Check-out data') }}</th>
                <th colspan="2">{{ __('Work Hours Summary') }}</th>
                {{-- <th rowspan="2">{{ __('Early departure (hour)') }}</th>
                <th rowspan="2">{{ __('Delay time (minute)') }}</th> --}}

            </x-filament-tables::row>
            <x-filament-tables::row>
                <th class="internal_cell">{{ __('From') }}</th>
                <th class="internal_cell">{{ __('To') }}</th>
                <th class="internal_cell">{{ __('Check-in') }}</th>
                <th class="internal_cell">{{ __('Status') }}</th>
                <th class="internal_cell">{{ __('Check-out') }}</th>
                <th class="internal_cell">{{ __('Status') }}</th>
                <th class="internal_cell">{{ __('Supposed') }}</th>
                <th class="internal_cell">{{ __('Total Hours Worked') }}</th>
            </x-filament-tables::row>

        </thead>

        <tbody>

            @foreach ($report_data as $date => $data_)
                @php
                    $data = array_values($data_);
                    // dd($data[0]['employee_name']);
                @endphp

                <x-filament-tables::row>
                    <x-filament-tables::cell>

                        {{ $data[0]['employee_name'] }}
                    </x-filament-tables::cell>

                    @if (count($data[0]['periods']) > 0)
                        <x-filament-tables::cell colspan="9" style="padding: 0;">
                            <x-filament-tables::table style="width: 100%">
                                @foreach ($data[0]['periods'] as $item)
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
                                        @endif
                                        @if (isset($item['attendances']['checkout']))
                                            <x-filament-tables::cell class="internal_cell">
                                                {{ $item['attendances']['checkout'][0]['check_time'] }}
                                            </x-filament-tables::cell>
                                            <x-filament-tables::cell class="internal_cell">
                                                {{ $item['attendances']['checkout'][0]['status'] }}
                                            </x-filament-tables::cell>
                                            <x-filament-tables::cell class="internal_cell">
                                                {{ $item['attendances']['checkout'][0]['supposed_duration_hourly'] }}
                                            </x-filament-tables::cell>

                                            <x-filament-tables::cell class="internal_cell">
                                                {{-- {{ $item['attendances']['checkout'][0]['actual_duration_hourly'] }} {{'  --  '}} --}}
                                                {{-- {{ $item['attendances']['checkout']['lastcheckout']['total_actual_duration_hourly'] }} --}}
                                                {{ $item['total_hours'] }}
                                            </x-filament-tables::cell>
                                        @endif
                                        @if (isset($item['attendances']['checkin']) && !isset($item['attendances']['checkout']))
                                            <x-filament-tables::cell colspan="4" class="internal_cell">
                                                {{ 'There is no checkout' }}
                                            </x-filament-tables::cell>
                                        @endif
                                        @if ($item['attendances'] == 'absent')
                                            <x-filament-tables::cell colspan="6">
                                                {{ 'Absent' }}
                                            </x-filament-tables::cell>
                                        @endif
                                    </x-filament-tables::row>
                                @endforeach
                            </x-filament-tables::table>
                        </x-filament-tables::cell>
                    @elseif (count($data[0]['periods']) == 0 && isset($data['holiday']))
                        <x-filament-tables::cell colspan="9">
                            {{ $data['holiday']['name'] }}
                        </x-filament-tables::cell>
                    @elseif (count($data[0]['periods']) == 0 && isset($data['leave']))
                        <x-filament-tables::cell colspan="9">
                            {{ $data['leave']['leave_type_name'] }}
                        </x-filament-tables::cell>
                        {{-- @elseif (isset($data[0]['no_periods']) && $data['no_periods']) --}}
                    @elseif (isset($data[0]['no_periods']) && !empty($data[0]['no_periods']))
                        <x-filament-tables::cell colspan="9">
                            {{ 'No any period for employee' }}
                        </x-filament-tables::cell>
                    @endif


                </x-filament-tables::row>
            @endforeach
        </tbody>

    </x-filament-tables::table>
    {{-- @else
        <div class="please_select_message_div" style="text-align: center;">

            <h1 class="please_select_message_text">{{ __('Please select an Branch') }}</h1>
        </div>
    @endif --}}
    {{-- <center style="font-weight: bolder;color:red">
    {{ 'The report is still under developing_' . 'التقرير لا يزال قيد التطوير' }}</center> --}}
</x-filament-panels::page>
