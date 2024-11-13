<x-filament-panels::page>
    {{ $this->getTableFiltersForm() }}

    {{-- <div class="table-container"> --}}
    <x-filament-tables::table class="w-full text-sm text-left pretty  reports" style="padding-top: 200px;">
        <thead class="fixed-header" style="padding-top: 200px;;top:64px;">
            <x-filament-tables::row class="header_report">
                <th colspan="3" class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }}">
                    {{-- <p>{{ 'Attendance employees report' }}</p> --}}

                    <p> Branch:
                        {{ $branch_id == '' ? ' ( All Branches ) ' : \App\Models\Branch::find($branch_id)?->name }}
                    </p>
                </th>
                <th colspan="2" class="no_border_right_left">

                </th>
                <th colspan="5" style="text-align: center; vertical-align: middle; padding:12px;"
                    class="{{ app()->getLocale() == 'en' ? 'no_border_left' : 'no_border_right' }}">
                    <img class="circle-image" src="{{ url('/') . '/' . 'storage/workbench.png' }}" alt="">
                </th>
            </x-filament-tables::row>

            <x-filament-tables::row class="fixed-header">
                <th rowspan="2">{{ __('Employee') }}</th>
                <th colspan="2">{{ __('Shift data') }}</th>

                <th colspan="4">{{ __('Check-in and Check-out data') }}</th>
                <th colspan="3">{{ __('Work Hours Summary') }}</th>

                {{-- <th rowspan="2">{{ __('Early departure (hour)') }}</th>
                <th rowspan="2">{{ __('Delay time (minute)') }}</th> --}}

            </x-filament-tables::row>
            <x-filament-tables::row class="fixed-header">
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

            @foreach ($report_data as $empId => $data_)
                @php

                    $date = array_keys($data_)[0];

                    $data = array_values($data_);

                @endphp

                <x-filament-tables::row>
                    <x-filament-tables::cell>

                        {{ $data[0]['employee_name'] }}
                    </x-filament-tables::cell>
                    @if (count($data[0]['periods']) > 0 &&
                            isset($data[0]['periods'][0]['attendances']) &&
                            //  &&
                            // is_array($data[0]['periods'][0]['attendances'])
                            // &&
                            // count($data[0]['periods'][0]['attendances']) > 0
                            !isset($data[0]['leave']))
                 <x-filament-tables::cell colspan="9" style="padding: 0;">
                    <x-filament-tables::table style="width: 100%">
                        @foreach ($data[0]['periods'] as $index => $item)
                            @php
                                $hasCheckin = isset($item['attendances']['checkin']);
                                $hasCheckout = isset($item['attendances']['checkout']);
                                $rowSpan = $hasCheckin && $hasCheckout ? max(count($item['attendances']['checkin']), count($item['attendances']['checkout'])) : 1;
                            @endphp
                
                            @for ($i = 0; $i < $rowSpan; $i++)
                                <x-filament-tables::row>
                                    @if ($i == 0)
                                        <x-filament-tables::cell rowspan="{{ $rowSpan }}" class="internal_cell">{{ $item['start_at'] }}</x-filament-tables::cell>
                                        <x-filament-tables::cell rowspan="{{ $rowSpan }}" class="internal_cell">{{ $item['end_at'] }}</x-filament-tables::cell>
                                    @endif
                
                                    {{-- Check-in --}}
                                    @if ($hasCheckin && isset($item['attendances']['checkin'][$i]))
                                        <x-filament-tables::cell class="internal_cell">
                                            {{ $item['attendances']['checkin'][$i]['check_time'] ?? '-' }}
                                        </x-filament-tables::cell>
                                        <x-filament-tables::cell class="internal_cell">
                                            {{ $item['attendances']['checkin'][$i]['status'] ?? '-' }}
                                        </x-filament-tables::cell>
                                    @elseif ($i == 0 && !$hasCheckin)
                                        <x-filament-tables::cell colspan="2" class="internal_cell">{{ 'There is no checkin' }}</x-filament-tables::cell>
                                    @endif
                
                                    {{-- Checkout --}}
                                    @if ($hasCheckout && isset($item['attendances']['checkout'][$i]))
                                        <x-filament-tables::cell class="internal_cell">
                                            {{ $item['attendances']['checkout'][$i]['check_time'] ?? '-' }}
                                        </x-filament-tables::cell>
                                        <x-filament-tables::cell class="internal_cell">
                                            {{ $item['attendances']['checkout'][$i]['status'] ?? '-' }}
                                        </x-filament-tables::cell>
                                        <x-filament-tables::cell class="internal_cell">
                                            {{ $item['attendances']['checkout'][$i]['supposed_duration_hourly'] ?? '-' }}
                                        </x-filament-tables::cell>
                                        <x-filament-tables::cell class="internal_cell" x-data="{ tooltip: false }">
                                            <button class="text-blue-500 hover:underline"
                                                @mouseenter="tooltip = true" @mouseleave="tooltip = false"
                                                wire:click="showDetails('{{ $date }}', {{ $data[0]['employee_id'] }}, {{ $item['period_id'] }})">
                                                {{ $item['total_hours'] }}
                                            </button>
                                            <div x-show="tooltip"
                                                class="absolute bg-gray-700 text-white text-xs rounded py-1 px-2"
                                                style="display: none; z-index: 10;">
                                                Multiple exit & entry
                                            </div>
                                        </x-filament-tables::cell>
                                        <x-filament-tables::cell class="internal_cell">
                                            {{ $item['attendances']['checkout'][$i]['approved_overtime'] ?? '-' }}
                                        </x-filament-tables::cell>
                                    @elseif ($i == 0 && !$hasCheckout)
                                        <x-filament-tables::cell colspan="4" class="internal_cell">{{ 'There is no checkout' }}</x-filament-tables::cell>
                                    @endif
                
                                    {{-- Absent --}}
                                    @if ($item['attendances'] == 'absent' && $i == 0)
                                        <x-filament-tables::cell colspan="7">{{ 'Absent' }}</x-filament-tables::cell>
                                    @endif
                                </x-filament-tables::row>
                            @endfor
                        @endforeach
                    </x-filament-tables::table>
                </x-filament-tables::cell>
                
                    @elseif (count($data[0]['periods']) == 0 && isset($data['holiday']))
                        <x-filament-tables::cell colspan="9">
                            {{ $data['holiday']['name'] }}
                        </x-filament-tables::cell>
                    @elseif (count($data[0]['periods']) > 0 && isset($data[0]['leave']))
                        <x-filament-tables::cell colspan="9">
                            123
                            {{ $data[0]['leave']['transaction_description'] }}
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
    {{-- </div> --}}
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
                            $totalMinutes = 0; // Store total minutes across all entries

                            foreach ($this->modalData as $detail) {
                                if ($detail['check_type'] === 'checkin') {
                                    $attendances[$detail['period_id']]['checkins'][] = $detail['check_time'];
                                } elseif ($detail['check_type'] === 'checkout') {
                                    $attendances[$detail['period_id']]['checkouts'][] = $detail['check_time'];
                                }
                            }

                            foreach ($attendances as $index => $attendance) {
                                $maxRows = max(
                                    count($attendance['checkins'] ?? []),
                                    count($attendance['checkouts'] ?? []),
                                );
                                for ($i = 0; $i < $maxRows; $i++) {
                                    $checkin = $attendance['checkins'][$i] ?? null;
                                    $checkout = $attendance['checkouts'][$i] ?? null;

                                    if ($checkin && $checkout) {
                                        $checkinTime = \Carbon\Carbon::createFromFormat('H:i:s', $checkin);
                                        $checkoutTime = \Carbon\Carbon::createFromFormat('H:i:s', $checkout);

                                        // Ensure check-out time is later than check-in time
                                        if ($checkoutTime->greaterThan($checkinTime)) {
                                            // Calculate hours and minutes separately
                                            $minutesDifference = $checkinTime->diffInMinutes($checkoutTime);
                                            $hours = intdiv($minutesDifference, 60);
                                            $minutes = $minutesDifference % 60;

                                            // Format the time as "Xh Ym"
                                            $attendances[$index]['total_hours'][$i] = "{$hours}h {$minutes}m";

                                            // Accumulate total minutes for all rows
                                            $totalMinutes += $minutesDifference;
                                        } else {
                                            $attendances[$index]['total_hours'][$i] = '0h 0m'; // Set to zero if invalid time
                                        }
                                    } else {
                                        $attendances[$index]['total_hours'][$i] = '-'; // If missing check-in or check-out
                                    }
                                }
                            }

                            // Convert accumulated total minutes to hours and minutes
                            $totalHours = intdiv($totalMinutes, 60);
                            $remainingMinutes = $totalMinutes % 60;
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
                            <td class="font-weight-bold">{{ $totalHours }}h {{ $remainingMinutes }}m</td>
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
