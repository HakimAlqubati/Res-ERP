<x-filament-panels::page>
    {{-- عرض نموذج الفلترة --}}
    {{ $this->getTableFiltersForm() }}

    {{-- التحقق من وجود بيانات --}}
    @if (!empty($branch_id))
        <table class="w-full text-sm text-left pretty reports">
            <thead class="fixed-header" style="top:64px;">
                <tr class="header_report">
                    <th colspan="3" class="no_border_right_left">
                        <p>{{ __('Attendance Tracking Report') }}</p>
                    </th>
                </tr>
                <tr>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Employee Name') }}</th>
                    <th>{{ __('Attendance Prediction') }}</th>
                    {{-- <th>{{ __('Check Time') }}</th>
                    <th>{{ __('Check Type') }}</th> --}}
                </tr>
            </thead>
            <tbody>
                @foreach ($report_data as $date => $data)
                    {{-- @if (empty($data['employees']))
                        <tr>
                            <td colspan="5" class="text-center">{{ __('No One') }}</td>
                        </tr>
                    @else --}}
                        @php $rowspan = count($data['employees']); @endphp
                        @foreach ($data['employees'] as $index => $employee)
                            <tr>
                                @if ($index === 0) <!-- Only show date for the first employee on this date -->
                                    <td rowspan="{{ $rowspan }}" class="text-center">{{ $date }}</td>
                                @endif
                                <td>{{ $employee['name'] }}</td>
                                <td>{{ ucfirst($employee['prediction']) }}</td>
                                {{-- @foreach ($employee['attendances'] as $attendance)
                                    <td>{{ $attendance['check_time'] }}</td>
                                    <td>{{ ucfirst($attendance['check_type']) }}</td>
                                @endforeach --}}
                            </tr>
                        @endforeach
                    {{-- @endif --}}
                @endforeach
            </tbody>
        </table>
    @else
    <div class="please_select_message_div" style="text-align: center;">

        <h1 class="please_select_message_text">{{ __('Please select an Branch') }}</h1>
    </div>
    @endif
</x-filament-panels::page>
