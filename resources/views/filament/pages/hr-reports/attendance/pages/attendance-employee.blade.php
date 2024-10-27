<x-filament-panels::page>
    {{ $this->getTableFiltersForm() }}

    @if (isset($employee_id) && is_numeric($employee_id))
        <x-filament-tables::table class="w-full text-sm text-left pretty reports">
            <thead class="fixed-header" style="top:64px;">
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
                    <th colspan="5" style="text-align: center; vertical-align: middle; padding:12px;"
                        class="{{ app()->getLocale() == 'en' ? 'no_border_left' : 'no_border_right' }}">
                        <img class="circle-image" src="{{ url('/') . '/' . 'storage/workbench.png' }}" alt="">
                    </th>
                </x-filament-tables::row>
                <x-filament-tables::row>
                    <th rowspan="2">{{ __('Date') }}</th>
                    <th colspan="2">{{ __('Shift data') }}</th>

                    <th colspan="4">{{ __('Check-in and Check-out data') }}</th>
                    <th colspan="3">{{ __('Work Hours Summary') }}</th>

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
                    <th class="internal_cell">{{ __('Approved') }}</th>
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
                                {{-- {{ dd($data['no_periods']) }} --}}
                            </x-filament-tables::cell>
                            @if (isset($data['no_periods']) && $data['no_periods'] == true)
                                <x-filament-tables::cell colspan="100%">
                                    {{ 'No periods in this date (' . $date . ')' }}
                                </x-filament-tables::cell>
                            @endif
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
                                                            {{ 'There is no checkout' }}
                                                        </x-filament-tables::cell>
                                                    @endif
                                                @endif
                                                @if (isset($item['attendances']['checkout']))
                                                    @if (!isset($item['attendances']['checkin']))
                                                        <x-filament-tables::cell colspan="2" class="internal_cell">
                                                            {{ 'There is no checkin' }}
                                                        </x-filament-tables::cell>
                                                    @endif
                                                    <x-filament-tables::cell class="internal_cell">
                                                        {{ $item['attendances']['checkout']['lastcheckout']['check_time'] }}
                                                    </x-filament-tables::cell>
                                                    <x-filament-tables::cell class="internal_cell">
                                                        {{ $item['attendances']['checkout']['lastcheckout']['status'] }}
                                                    </x-filament-tables::cell>
                                                    <x-filament-tables::cell class="internal_cell">
                                                        {{ $item['attendances']['checkout']['lastcheckout']['supposed_duration_hourly'] }}
                                                    </x-filament-tables::cell>

                                                    <x-filament-tables::cell class="internal_cell"
                                                        x-data="{ tooltip: false }">
                                                        <button class="text-blue-500 hover:underline"
                                                            @mouseenter="tooltip = true" @mouseleave="tooltip = false"
                                                            wire:click="showDetails('{{ $date }}', {{ $employee_id }},{{ $item['period_id'] }})">
                                                            {{ $item['total_hours'] }}
                                                        </button>

                                                        <div x-show="tooltip"
                                                            class="absolute bg-gray-700 text-white text-xs rounded py-1 px-2"
                                                            style="display: none; z-index: 10;">
                                                            Multiple exit & entery
                                                        </div>
                                                    </x-filament-tables::cell>
 
                                                    <x-filament-tables::cell class="internal_cell">


                                                        {{ $item['attendances']['checkout']['lastcheckout']['approved_overtime'] }}

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
                                    {{ $data['leave']['transaction_description'] }}
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
    @if ($showDetailsModal)

        <div class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50" style="z-index: 9999;">
            <div class="bg-white p-6 rounded-lg shadow-lg" style="width: 90%; max-width: 700px; color: black;">
                <h2 class="text-xl font-bold mb-4 text-center" style="color: #333;">Attendance Details</h2>

                <!-- Bootstrap-styled striped table -->
                <table class="table table-striped table-bordered" style="color: #333;">
                    <thead class="thead-dark">
                        <tr>
                            <th style="width: 10%;">#</th>
                            {{-- <th style="width: 30%;">Attendance</th> --}}
                            <th style="width: 30%;">Check-in</th>
                            <th style="width: 30%;">Check-out</th>
                            <th style="width: 30%;">Total Hours</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $attendances = [];
                            $totalHours = 0; // Variable to store total hours across all entries

                            foreach ($this->modalData as $detail) {
                                // Store each checkin and checkout for a given period ID
                                if ($detail['check_type'] === 'checkin') {
                                    $attendances[$detail['period_id']]['checkins'][] = $detail['check_time'];
                                } elseif ($detail['check_type'] === 'checkout') {
                                    $attendances[$detail['period_id']]['checkouts'][] = $detail['check_time'];
                                }
                            }

                            // Calculate total hours for each pair of check-in and check-out
                            // Calculate total hours for each pair of check-in and check-out
                            foreach ($attendances as $index => $attendance) {
                                $maxRows = max(
                                    count($attendance['checkins'] ?? []),
                                    count($attendance['checkouts'] ?? []),
                                );
                                for ($i = 0; $i < $maxRows; $i++) {
                                    $checkin = $attendance['checkins'][$i] ?? null;
                                    $checkout = $attendance['checkouts'][$i] ?? null;

                                    // Calculate hours if both check-in and check-out exist
                                    if ($checkin && $checkout) {
                                        $checkinTime = \Carbon\Carbon::createFromFormat('H:i:s', $checkin);
                                        $checkoutTime = \Carbon\Carbon::createFromFormat('H:i:s', $checkout);

                                        // Ensure correct time difference calculation, and only add if checkout is later than checkin
                                        if ($checkoutTime->greaterThan($checkinTime)) {
                                            $hours =
                                                $checkoutTime->diffInHours($checkinTime) +
                                                round(($checkoutTime->diffInMinutes($checkinTime) % 60) / 60, 2); // Add minutes as decimal
                                            $attendances[$index]['total_hours'][$i] = abs($hours); // Use abs() to convert to positive
                                            $totalHours += abs($hours); // Accumulate total hours as positive
                                        } else {
                                            $attendances[$index]['total_hours'][$i] = '-'; // Ignore invalid times
                                        }
                                    } else {
                                        $attendances[$index]['total_hours'][$i] = '-'; // If missing check-in or check-out
                                    }
                                }
                            }

                        @endphp

                        @foreach ($attendances as $index => $attendance)
                            @php
                                $maxRows = max(
                                    count($attendance['checkins'] ?? []),
                                    count($attendance['checkouts'] ?? []),
                                );
                            @endphp
                            @for ($i = 0; $i < $maxRows; $i++)
                                <tr>
                                    @if ($i == 0)
                                        <td rowspan="{{ $maxRows }}">{{ $loop->iteration }}</td>
                                        {{-- <td rowspan="{{ $maxRows }}">{{ ordinal($loop->iteration) }} Attendance --}}
                                        </td>
                                    @endif
                                    <td>{{ $attendance['checkins'][$i] ?? '-' }}</td>
                                    <td>{{ $attendance['checkouts'][$i] ?? '-' }}</td>
                                    <td>{{ $attendance['total_hours'][$i] }}</td>
                                </tr>
                            @endfor
                        @endforeach

                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-right font-weight-bold">Total Hours:</td>
                            <td class="font-weight-bold">{{ round($totalHours, 2) }} hours</td>
                        </tr>
                    </tfoot>
                </table>

                <div class="text-center mt-4">
                    <button wire:click="$set('showDetailsModal', false)" class="btn btn-primary"
                        style="width: 100px;color:black">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif

</x-filament-panels::page>
