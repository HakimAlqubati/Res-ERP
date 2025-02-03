<x-filament-panels::page>

    {{ $this->getTableFiltersForm() }}

    <style>
        table {
            /* border-collapse: collapse; */
            width: 100%;
            border-collapse: inherit;
            border-spacing: initial;
        }

        /* Print-specific styles */
        @media print {

            /* Hide everything except the table */
            body * {
                visibility: hidden;
            }

            #report-table,
            #report-table * {
                visibility: visible;
            }

            #report-table {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
            }

            /* Add borders and spacing for printed tables */
            table {
                border-collapse: collapse;
                width: 100%;
            }

            th,
            td {
                border: 1px solid #000;
                padding: 10px;
                font-size: 12px;
                /* Adjust font size for better readability */
                color: #000;
                /* Black text for headers */
            }

            th {
                background-color: #ddd;
                /* Light gray background for table headers */

            }

            td {
                background-color: #fff;
                /* White background for cells */
            }

        }
    </style>
    {{-- Add the Print Button --}}
    <div class="text-right mb-4">
        <button onclick="printReport()" class="btn btn-print">
            {{ __('Print Report') }}
        </button>
    </div>

    {{-- <div class="table-container"> --}}
    <x-filament-tables::table class="w-full text-sm text-left pretty  reports" id="report-table" style="padding-top: 5px;">
        <thead class="fixed-header" style="padding-top: 5px;;top:64px;">
            <x-filament-tables::row class="header_report">
                <th colspan="3"
                    class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }} company-info">
                    {{-- <p>{{ 'Attendance employees report' }}</p> --}}
                    <div style="width: 100%;">

                        <img style="display: inline-block;"
                            src="{{ asset('/storage/' . setting('company_logo') . '') }}" alt="Company Logo"
                            class="logo-left">
                    </div>
                </th>
                <th colspan="3" class="no_border_right_left">

                    <div style="width: 100;">

                        <p>
                            {{ $branch_id == '' ? ' ( All Branches ) ' : \App\Models\Branch::find($branch_id)?->name }}
                        </p>
                    </div>

                </th>
                <th colspan="4" style="text-align: center; vertical-align: middle; padding:12px;"
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
                    <x-filament-tables::cell style="text-align: left">

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
                                            <x-filament-tables::cell class="internal_cell status_cell">
                                                {{ $item['attendances']['checkin'][0]['status'] }}
                                            </x-filament-tables::cell>
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
                                            <x-filament-tables::cell class="internal_cell status_cell">
                                                {{ $item['attendances']['checkout'][0]['status'] }}
                                            </x-filament-tables::cell>
                                            <x-filament-tables::cell class="internal_cell">
                                                {{ $item['attendances']['checkout'][0]['supposed_duration_hourly'] }}
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
                                                    Multiple exit & entery
                                                </div>
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

        <tfoot>
            <x-filament-tables::row>
                <th colspan="7" class="text-right font-bold">{{ __('Total') }}</th>
                <td class="text-center">{{ $totalSupposed }}</td>
                <td class="text-center">{{ $totalWorked }}</td>
                <td class="text-center">{{ $totalApproved }}</td>
            </x-filament-tables::row>
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
                                        // Ensure check-out time is later than check-in time
                                        $checkinTime = \Carbon\Carbon::createFromFormat('H:i:s', $checkin);
                                        $checkoutTime = \Carbon\Carbon::createFromFormat('H:i:s', $checkout);
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
                                            // Calculate hours and minutes separately
                                            $checkinTime = \Carbon\Carbon::createFromFormat('H:i:s', $checkin);
                                            $checkoutTime = \Carbon\Carbon::createFromFormat('H:i:s', $checkout);
                                            $checkoutTime->addDay(); // Add a day to the checkout time

                                            // dd($checkinTime,$checkoutTime);
                                            // Calculate hours and minutes separately
                                            $minutesDifference = $checkinTime->diffInMinutes($checkoutTime);
                                            $hours = intdiv($minutesDifference, 60);
                                            $minutes = $minutesDifference % 60;

                                            // Format the time as "Xh Ym"
                                            $attendances[$index]['total_hours'][$i] = "{$hours}h {$minutes}m";

                                            // Accumulate total minutes for all rows
                                            $totalMinutes += $minutesDifference;
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

{{-- JavaScript for Printing --}}
<script>
    function printReport() {
        // Hide the print button and modal while printing
        const printButton = document.querySelector('button[onclick="printReport()"]');
        const modal = document.querySelector('.fixed.inset-0');

        if (printButton) printButton.style.display = 'none';
        if (modal) modal.style.display = 'none';

        window.print();

        // Restore visibility after printing
        if (printButton) printButton.style.display = 'block';
        if (modal) modal.style.display = 'flex';
    }
</script>
