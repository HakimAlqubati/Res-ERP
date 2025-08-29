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
    <table class="w-full text-sm text-left pretty  reports" id="report-table">
        <thead>
            <tr class="header_report">
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
            </tr>

            <tr>
                <th rowspan="2">{{ __('Employee') }}</th>
                <th colspan="2">{{ __('Shift data') }}</th>

                <th colspan="4">{{ __('Check-in and Check-out data') }}</th>
                <th colspan="2">{{ __('Work Hours Summary') }}</th>
                {{-- <th rowspan="2">{{ __('Early departure (hour)') }}</th>
                <th rowspan="2">{{ __('Delay time (minute)') }}</th> --}}

            </tr>
            <tr>
                <th class="internal_cell">{{ __('From') }}</th>
                <th class="internal_cell">{{ __('To') }}</th>
                <th class="internal_cell">{{ __('Check-in') }}</th>
                <th class="internal_cell">{{ __('Status') }}</th>
                <th class="internal_cell">{{ __('Check-out') }}</th>
                <th class="internal_cell">{{ __('Status') }}</th>
                <th class="internal_cell">{{ __('Supposed') }}</th>
                <th class="internal_cell">{{ __('Total Hours Worked') }}</th>
            </tr>

        </thead>

        <tbody>

            @foreach ($report_data as $date => $data_)
                @php
                    $data = array_values($data_);
                    // dd($data[0]['employee_name']);
                @endphp

                <tr>
                    <td>

                        {{ $data[0]['employee_name'] }}
                    </td>

                    @if (count($data[0]['periods']) > 0)
                        <td colspan="9" style="padding: 0;">
                            <table style="width: 100%">
                                @foreach ($data[0]['periods'] as $item)
                                    <tr>
                                        <td
                                            class="internal_cell">{{ $item['start_at'] }}</td>
                                        <td
                                            class="internal_cell">{{ $item['end_at'] }}</td>

                                        @if (isset($item['attendances']['checkin']))
                                            <td class="internal_cell">
                                                {{ $item['attendances']['checkin'][0]['check_time'] }}
                                            </td>
                                            <td class="internal_cell">
                                                {{ $item['attendances']['checkin'][0]['status'] }}
                                            </td>
                                        @endif
                                        @if (isset($item['attendances']['checkout']))
                                            <td class="internal_cell">
                                                {{ $item['attendances']['checkout'][0]['check_time'] }}
                                            </td>
                                            <td class="internal_cell">
                                                {{ $item['attendances']['checkout'][0]['status'] }}
                                            </td>
                                            <td class="internal_cell">
                                                {{ $item['attendances']['checkout'][0]['supposed_duration_hourly'] }}
                                            </td>

                                            <td class="internal_cell">
                                                {{-- {{ $item['attendances']['checkout'][0]['actual_duration_hourly'] }} {{'  --  '}} --}}
                                                {{-- {{ $item['attendances']['checkout']['lastcheckout']['total_actual_duration_hourly'] }} --}}
                                                {{ $item['total_hours'] }}
                                            </td>
                                        @endif
                                        @if (isset($item['attendances']['checkin']) && !isset($item['attendances']['checkout']))
                                            <td colspan="4" class="internal_cell">
                                                {{ 'There is no checkout' }}
                                            </td>
                                        @endif
                                        @if ($item['attendances'] == 'absent')
                                            <td colspan="6">
                                                {{ 'Absent' }}
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </table>
                        </td>
                    @elseif (count($data[0]['periods']) == 0 && isset($data['holiday']))
                        <td colspan="9">
                            {{ $data['holiday']['name'] }}
                        </td>
                    @elseif (count($data[0]['periods']) == 0 && isset($data['leave']))
                        <td colspan="9">
                            {{ $data['leave']['leave_type_name'] }}
                        </td>
                        {{-- @elseif (isset($data[0]['no_periods']) && $data['no_periods']) --}}
                    @elseif (isset($data[0]['no_periods']) && !empty($data[0]['no_periods']))
                        <td colspan="9">
                            {{ 'No any period for employee' }}
                        </td>
                    @endif


                </tr>
            @endforeach
        </tbody>

    </table>
    {{-- @else
        <div class="please_select_message_div" style="text-align: center;">

            <h1 class="please_select_message_text">{{ __('Please select an Branch') }}</h1>
        </div>
    @endif --}}
    {{-- <center style="font-weight: bolder;color:red">
    {{ 'The report is still under developing_' . 'التقرير لا يزال قيد التطوير' }}</center> --}}
</x-filament-panels::page>
