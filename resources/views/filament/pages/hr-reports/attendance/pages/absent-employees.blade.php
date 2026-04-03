<x-filament-panels::page>
    {{-- عرض نموذج الفلترة --}}
    {{ $this->getTableFiltersForm() }}

    {{-- التحقق من وجود بيانات الفرع --}}
    @if (!empty($branch_id))
    <table class="w-full text-sm text-left pretty reports">
        <thead class="fixed-header" style="top:64px;">
            <tr class="header_report">
                <th colspan="2" class="no_border_right_left">
                    <p>{{ __('Employee Absence Report') }}</p>
                </th>
                <th colspan="3" class="no_border_right_left">
                    <p>
                        @if($date_from === $date_to)
                            {{ __('Date: ') . $date_from }}
                        @else
                            {{ __('Date From: ') . $date_from }} | {{ __('Date To: ') . $date_to }}
                        @endif
                    </p>
                </th>
            </tr>
            <tr>
                <th>{{ __('No.') }}</th>
                <th>{{ __('Employee Name') }}</th>
                <th>{{ __('Date') }}</th>
                <th>{{ __('Start Time') }}</th>
                <th>{{ __('End Time') }}</th>
            </tr>
        </thead>
        <tbody>
            @if (count($report_data) > 0)
            @php $rowNumber = 1; @endphp
            @foreach ($report_data as $item)
                @php
                $employeeName = $item['employee_name'] ?? 'N/A';
                $absences = $item['absences'] ?? [];
                @endphp

                @foreach ($absences as $dayData)
                    @php
                    $date = $dayData['date'] ?? '-';
                    $periods = $dayData['periods'] ?? [];
                    @endphp

                    @foreach ($periods as $period)
                    <tr>
                        <td>{{ $rowNumber++ }}</td>
                        <td>{{ $employeeName }}</td>
                        <td>{{ $date }}</td>
                        <td>{{ $period['start_time'] ?? '-' }}</td>
                        <td>{{ $period['end_time'] ?? '-' }}</td>
                    </tr>
                    @endforeach
                @endforeach
            @endforeach
            @else
            <tr>
                <td colspan="5" style="text-align: center;">
                    {{ __('No employees absent for this date range.') }}
                </td>
            </tr>
            @endif
        </tbody>
    </table>
    @else
    <div class="please_select_message_div" style="text-align: center;">
        <h1 class="please_select_message_text">{{ __('Please select a Branch') }}</h1>
    </div>
    @endif
</x-filament-panels::page>