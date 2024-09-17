<x-filament-panels::page>
    {{ $this->getTableFiltersForm() }}

    {{-- @if (isset($branch_id) && is_numeric($branch_id)) --}}
    <x-filament-tables::table class="w-full text-sm text-left pretty  ">
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
                <th colspan="4" style="text-align: center; vertical-align: middle;"
                    class="{{ app()->getLocale() == 'en' ? 'no_border_left' : 'no_border_right' }}">
                    <img class="circle-image" src="{{ url('/') . '/' . 'storage/workbench.png' }}" alt="">
                </th>
            </x-filament-tables::row>
            <x-filament-tables::row>
                <th rowspan="2">{{ __('Employee') }}</th>
                @foreach ($work_periods as $work_period)
                    <th colspan="2"> {{ $work_period['name'] }} </th>
                @endforeach
            </x-filament-tables::row>

            <x-filament-tables::row>
                @if ($work_periods)

                    @foreach ($work_periods as $work_period)
                        <th>
                            {{ $work_period['start_at'] }}
                        </th>
                        <th>
                            {{ $work_period['end_at'] }}
                        </th>
                    @endforeach
                @else
                    <th colspan="100%"> <p style="color: red;">{{ 'No periods for choosen date' }}</p> </th>
                @endif
            </x-filament-tables::row>
        </thead>

        <tbody>
            @foreach ($report_data as $key_emp_name => $item_data)
                <x-filament-tables::row>
                    <x-filament-tables::cell>
                        {{ $key_emp_name }}
                    </x-filament-tables::cell>

                    @foreach ($work_periods as $work_period)
                        @php
                            // dd($item_data);
                        @endphp
                        @if (count($item_data[$work_period['id']]) == 1)
                            <x-filament-tables::cell colspan="2">
                                @if (isset($item_data[$work_period['id']][0]->check_type) && $item_data[$work_period['id']][0]->check_type == 'Absent')
                                    <p class="absent">{{ 'Absent' }}</p>
                                @endif
                                @if (isset($item_data[$work_period['id']][0]->holiday_name) &&
                                        $item_data[$work_period['id']][0]->check_type == 'Holiday')
                                    <p class="absent">{{ 'Absent' }}</p>
                                @endif
                            </x-filament-tables::cell>
                        @elseif (count($item_data[$work_period['id']]) >= 2)
                            @foreach ($item_data[$work_period['id']] as $item)
                                <x-filament-tables::cell>
                                    @if (isset($item->check_type) && $item->check_type == \App\Models\Attendance::CHECKTYPE_CHECKIN)
                                        {{ $item->check_time }}
                                    @elseif (isset($item->check_type) && $item->check_type == \App\Models\Attendance::CHECKTYPE_CHECKOUT)
                                        {{ $item->check_time }}
                                    @endif
                                </x-filament-tables::cell>
                            @endforeach
                        @endif
                    @endforeach
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
