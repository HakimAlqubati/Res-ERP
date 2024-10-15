<x-filament-panels::page>
    {{ $this->getTableFiltersForm() }}

    <div class="table-container">
        <x-filament-tables::table class="w-full text-sm text-left pretty reports">
            <thead>
                <x-filament-tables::row class="header_report fixed-header"> <!-- Add fixed-header class -->
                    <th class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }}">
                        <p>{{ 'Attendance employees report' }}</p>
                        <p> Branch: {{ $branch_id == '' ? ' ( All Branches ) ' : \App\Models\Branch::find($branch_id)?->name }}</p>
                    </th>
                    <th colspan="4" class="no_border_right_left"></th>
                    <th colspan="4" style="text-align: center; vertical-align: middle; padding:12px;"
                        class="{{ app()->getLocale() == 'en' ? 'no_border_left' : 'no_border_right' }}">
                        <img class="circle-image" src="{{ url('/') . '/' . 'storage/workbench.png' }}" alt="">
                    </th>
                </x-filament-tables::row>

                <x-filament-tables::row class="fixed-header"> <!-- Add fixed-header class -->
                    <th rowspan="2">{{ __('Employee') }}</th>
                    <th colspan="2">{{ __('Shift data') }}</th>
                    <th colspan="4">{{ __('Check-in and Check-out data') }}</th>
                    <th colspan="2">{{ __('Work Hours Summary') }}</th>
                </x-filament-tables::row>
                <x-filament-tables::row class="fixed-header"> <!-- Add fixed-header class -->
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
                                            <x-filament-tables::cell class="internal_cell">{{ $item['start_at'] }}</x-filament-tables::cell>
                                            <x-filament-tables::cell class="internal_cell">{{ $item['end_at'] }}</x-filament-tables::cell>
                                            @if (isset($item['attendances']['checkin']))
                                                <x-filament-tables::cell class="internal_cell">{{ $item['attendances']['checkin'][0]['check_time'] }}</x-filament-tables::cell>
                                                <x-filament-tables::cell class="internal_cell">{{ $item['attendances']['checkin'][0]['status'] }}</x-filament-tables::cell>
                                            @endif
                                            @if (isset($item['attendances']['checkout']))
                                                <x-filament-tables::cell class="internal_cell">{{ $item['attendances']['checkout'][0]['check_time'] }}</x-filament-tables::cell>
                                                <x-filament-tables::cell class="internal_cell">{{ $item['attendances']['checkout'][0]['status'] }}</x-filament-tables::cell>
                                                <x-filament-tables::cell class="internal_cell">{{ $item['attendances']['checkout']['lastcheckout']['total_actual_duration_hourly'] }}</x-filament-tables::cell>
                                            @endif
                                            @if (isset($item['attendances']['checkin']) && !isset($item['attendances']['checkout']))
                                                <x-filament-tables::cell colspan="4" class="internal_cell">{{ 'There is no checkout' }}</x-filament-tables::cell>
                                            @endif
                                            @if ($item['attendances'] == 'absent')
                                                <x-filament-tables::cell colspan="6">{{ 'Absent' }}</x-filament-tables::cell>
                                            @endif
                                        </x-filament-tables::row>
                                    @endforeach
                                </x-filament-tables::table>
                            </x-filament-tables::cell>
                        @else
                            <x-filament-tables::cell colspan="9">{{ 'No periods for employee' }}</x-filament-tables::cell>
                        @endif
                    </x-filament-tables::row>
                @endforeach
            </tbody>
        </x-filament-tables::table>
    </div>
</x-filament-panels::page>
