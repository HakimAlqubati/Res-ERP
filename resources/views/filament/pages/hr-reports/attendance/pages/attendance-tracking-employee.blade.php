<x-filament-panels::page>
    {{-- عرض نموذج الفلترة --}}
    {{ $this->getTableFiltersForm() }}

    {{-- التحقق من وجود بيانات --}}
    @if (!empty($branch_id))
        <x-filament-tables::table class="w-full text-sm text-left pretty reports">
            <thead class="fixed-header" style="top:64px;">
                <x-filament-tables::row class="header_report">
                    <th colspan="3" class="no_border_right_left">
                        <p>{{ __('Attendance Tracking Report') }}</p>
                    </th>
                </x-filament-tables::row>
                <x-filament-tables::row>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Employee Name') }}</th>
                    <th>{{ __('Attendance Prediction') }}</th>
                    {{-- <th>{{ __('Check Time') }}</th>
                    <th>{{ __('Check Type') }}</th> --}}
                </x-filament-tables::row>
            </thead>
            <tbody>
                @foreach ($report_data as $date => $data)
                    {{-- @if (empty($data['employees']))
                        <x-filament-tables::row>
                            <x-filament-tables::cell colspan="5" class="text-center">{{ __('No One') }}</x-filament-tables::cell>
                        </x-filament-tables::row>
                    @else --}}
                        @php $rowspan = count($data['employees']); @endphp
                        @foreach ($data['employees'] as $index => $employee)
                            <x-filament-tables::row>
                                @if ($index === 0) <!-- Only show date for the first employee on this date -->
                                    <x-filament-tables::cell rowspan="{{ $rowspan }}" class="text-center">{{ $date }}</x-filament-tables::cell>
                                @endif
                                <x-filament-tables::cell>{{ $employee['name'] }}</x-filament-tables::cell>
                                <x-filament-tables::cell>{{ ucfirst($employee['prediction']) }}</x-filament-tables::cell>
                                {{-- @foreach ($employee['attendances'] as $attendance)
                                    <x-filament-tables::cell>{{ $attendance['check_time'] }}</x-filament-tables::cell>
                                    <x-filament-tables::cell>{{ ucfirst($attendance['check_type']) }}</x-filament-tables::cell>
                                @endforeach --}}
                            </x-filament-tables::row>
                        @endforeach
                    {{-- @endif --}}
                @endforeach
            </tbody>
        </x-filament-tables::table>
    @else
    <div class="please_select_message_div" style="text-align: center;">

        <h1 class="please_select_message_text">{{ __('Please select an Branch') }}</h1>
    </div>
    @endif
</x-filament-panels::page>
