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
                    <th colspan="2" class="no_border_right_left">
                        <p>{{ __('Date: ') . $date }}</p>
                    </th>
                </tr>
                <tr>
                    <th>{{ __('No.') }}</th>
                    <th>{{ __('Employee Name') }}</th>
                    <th>{{ __('Start Time') }}</th>
                    <th>{{ __('End Time') }}</th>
                </tr>
            </thead>
            <tbody>
                @if (count($report_data) > 0)
                    @php $rowNumber = 1; @endphp
                    @foreach ($report_data as $employee)
                        @foreach ($employee['periods'] as $period)
                            <tr>
                                <td>{{ $rowNumber++ }}</td>
                                <td>{{ $employee['name'] }}</td>
                                <td>{{ $period['start_at'] }}</td>
                                <td>{{ $period['end_at'] }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                @else
                    <tr>
                        <td colspan="4" style="text-align: center;">
                            {{ __('No employees absent for this date.') }}
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
