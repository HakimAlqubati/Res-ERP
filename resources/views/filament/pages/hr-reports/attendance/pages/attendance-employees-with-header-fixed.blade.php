<x-filament-panels::page>
    {{ $this->getTableFiltersForm() }}

    {{-- <div class="table-container"> --}}
    <x-filament-tables::table class="w-full text-sm text-left pretty  reports" style="padding-top: 200px;">
        <thead class="fixed-header" style="padding-top: 200px;;top:64px;">
            <x-filament-tables::row class="header_report">
                <th class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }}">
                    <p>{{ 'Attendance employees report' }}</p>

                    <p> Branch:
                        {{ $branch_id == '' ? ' ( All Branches ) ' : \App\Models\Branch::find($branch_id)?->name }}
                    </p>
                </th>
                <th colspan="4" class="no_border_right_left">

                </th>
                <th colspan="5" style="text-align: center; vertical-align: middle; padding:12px;"
                    class="{{ app()->getLocale() == 'en' ? 'no_border_left' : 'no_border_right' }}">
                    {{-- <img class="circle-image" src="{{ url('/') . '/' . 'storage/workbench.png' }}" alt=""> --}}
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
                                @foreach ($data[0]['periods'] as $item)
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
                                        @endif
                                        @if (isset($item['attendances']['checkout']))
                                            <x-filament-tables::cell class="internal_cell">
                                                {{ $item['attendances']['checkout'][0]['check_time'] }}
                                            </x-filament-tables::cell>
                                            <x-filament-tables::cell class="internal_cell">
                                                {{ $item['attendances']['checkout'][0]['status'] }}
                                            </x-filament-tables::cell>
                                            <x-filament-tables::cell class="internal_cell">
                                                {{ $item['attendances']['checkout'][0]['supposed_duration_hourly'] }}
                                            </x-filament-tables::cell>

                                            <x-filament-tables::cell class="internal_cell">
                                                {{-- {{ $item['attendances']['checkout'][0]['actual_duration_hourly'] }} {{'  --  '}} --}}
                                                {{-- {{ $item['attendances']['checkout']['lastcheckout']['total_actual_duration_hourly'] }} --}}
                                                {{-- <button class="button"
                                                        wire:click="showDetails('{{ $date }}', {{ $data[0]['employee_id'] }},{{ $item['period_id'] }})"
                                                        class="text-blue-500 hover:underline">
                                                        {{ $item['total_hours'] }}
                                                    </button> --}}

                                                {{-- <button class="text-blue-500 hover:underline"
                                                    wire:click="showDetails('{{ $date }}', {{ $data[0]['employee_id'] }},{{ $item['period_id'] }})">
                                                    {{ $item['total_hours'] }}
                                                </button> --}}

                                                <button class="text-blue-500 hover:underline"
                                                    wire:click="showDetails('{{ $date }}', {{ $data[0]['employee_id'] }}, {{ $item['period_id'] }})">
                                                    {{ $item['total_hours'] }}
                                                </button>
                                            </x-filament-tables::cell>

                                            <x-filament-tables::cell class="internal_cell">
                                                {{ $item['attendances']['checkout']['lastcheckout']['approved_overtime'] }}
                                            </x-filament-tables::cell>
                                        @endif
                                        @if (isset($item['attendances']['checkin']) && !isset($item['attendances']['checkout']))
                                            <x-filament-tables::cell colspan="4" class="internal_cell">
                                                {{ 'There is no checkout' }}
                                            </x-filament-tables::cell>
                                        @endif
                                        @if ($item['attendances'] == 'absent')
                                            <x-filament-tables::cell colspan="7">
                                                {{ 'Absent' }}
                                            </x-filament-tables::cell>
                                        @endif
                                    </x-filament-tables::row>
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
                                            $attendances[$index]['total_hours'][$i] = $hours;
                                            $totalHours += $hours;
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
