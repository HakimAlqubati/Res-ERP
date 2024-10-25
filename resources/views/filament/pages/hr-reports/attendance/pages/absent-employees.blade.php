<x-filament-panels::page>
    {{-- عرض نموذج الفلترة --}}
    {{ $this->getTableFiltersForm() }}

    {{-- التحقق من وجود بيانات الموظف --}}
    @if (!empty($report_data))
        <x-filament-tables::table class="w-full text-sm text-left pretty reports">
            <thead class="fixed-header" style="top:64px;">
                <x-filament-tables::row class="header_report">
                    <th colspan="2" class="no_border_right_left">
                        <p>{{ __('Employee Attendance Report') }}</p>
                    </th>
                    <th colspan="3" class="no_border_right_left">
                        <p>{{ __('Date: ') . now()->format('Y-m-d') }}</p>
                    </th>
                </x-filament-tables::row>
                <x-filament-tables::row>
                    <th>{{ __('No.') }}</th>
                    <th>{{ __('Employee Name') }}</th>
                    <th>{{ __('Period Name') }}</th>
                    <th>{{ __('Start Time') }}</th>
                    <th>{{ __('End Time') }}</th>
                </x-filament-tables::row>
            </thead>
            <tbody>
                @php $rowNumber = 1; @endphp
                @foreach ($report_data as $employee)
                    @foreach ($employee['periods'] as $period)
                        <x-filament-tables::row>
                            <x-filament-tables::cell>{{ $rowNumber++ }}</x-filament-tables::cell>
                            <x-filament-tables::cell>{{ $employee['name'] }}</x-filament-tables::cell>
                            <x-filament-tables::cell>{{ $period['name'] }}</x-filament-tables::cell>
                            <x-filament-tables::cell>{{ $period['start_at'] }}</x-filament-tables::cell>
                            <x-filament-tables::cell>{{ $period['end_at'] }}</x-filament-tables::cell>
                        </x-filament-tables::row>
                    @endforeach
                @endforeach
            </tbody>
        </x-filament-tables::table>
    @else
        <div class="please_select_message_div" style="text-align: center;">
            <h1 class="please_select_message_text">{{ __('Please select a Branch') }}</h1>
        </div>
    @endif
</x-filament-panels::page>
