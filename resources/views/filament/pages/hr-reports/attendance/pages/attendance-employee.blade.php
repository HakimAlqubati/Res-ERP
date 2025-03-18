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

        .btn-print,
        .btn-primary {
            padding: 8px 16px;
            font-size: 14px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            display: inline-flex;
            align-items: center;
        }

        .btn-print {
            background-color: #4CAF50;
            color: white;
        }

        .btn-print:hover {
            background-color: #45a049;
            transform: scale(1.05);
        }

        .btn-primary {
            background-color: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            transform: scale(1.05);
        }

        .btn-print i,
        .btn-primary i {
            margin-right: 8px;
        }
    </style>
    {{-- Add the Print Button --}}
    <div class="text-right mb-4">
        <button onclick="printReport()" class="btn btn-print">
            &#128438; {{ __('Print Report') }}
        </button>

        <button onclick="exportToExcel()" class="btn btn-primary">
            &#128200; {{ __('Export to Excel') }}
        </button>

    </div>
    @if (isset($employee_id) && is_numeric($employee_id))
        <x-filament-tables::table class="w-full text-sm text-left pretty reports" id="report-table">
            <thead class="fixed-header" style="top:64px;">
                <x-filament-tables::row class="header_report">
                    <th colspan="4" class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }}">
                        {{-- <p>{{ 'Attendance employee report' }}</p> --}}
                        <p>({{ isset($employee_id) && is_numeric($employee_id) ? \App\Models\Employee::find($employee_id)->name : __('lang.choose_branch') }})
                        </p>
                    </th>
                    <th colspan="2" class="no_border_right_left">
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
                    <th rowspan="2" style="display: {{ $show_day ? 'table-cell' : 'none' }};">{{ __('Day') }}
                    </th>
                    <th rowspan="2">{{ __('Date') }}</th>
                    <th colspan="2">{{ __('Shift data') }}</th>

                    <th colspan="4">{{ __('Check-in and Check-out data') }}</th>
                    <th colspan="3">{{ __('Work Hours Summary') }}</th>

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
                            <x-filament-tables::cell style="display: {{ $show_day ? 'table-cell' : 'none' }};">
                                {{ $data['day'] }}
                            </x-filament-tables::cell>
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
                                                    <x-filament-tables::cell class="internal_cell status_cell">
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
                                                    <x-filament-tables::cell class="internal_cell status_cell">
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
                                    {{ $data['leave']['transaction_description'] ?? '' }}
                                </x-filament-tables::cell>
                            @endif


                        </x-filament-tables::row>
                    @endforeach
                @endif
            </tbody>

            <tfoot>
                <x-filament-tables::row>
                    <th colspan="{{ $show_day ? 8 : 7 }}" class="text-right font-bold">{{ __('Total') }}</th>
                    <td class="text-center">{{ $totalSupposed }}</td>
                    <td class="text-center">{{ $totalWorked }}</td>
                    <td class="text-center">{{ $totalApproved }}</td>
                </x-filament-tables::row>
            </tfoot>
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
<script>
    function exportToExcel() {
        let table = document.getElementById("report-table");
        let rows = [];

        // Loop through each row of the table
        for (let i = 0; i < table.rows.length; i++) {
            let row = [];
            let cells = table.rows[i].cells;

            for (let j = 0; j < cells.length; j++) {
                let cell = cells[j];

                // **Fix: Check if the cell contains a nested table**
                let nestedTable = cell.querySelector("table");
                if (nestedTable) {
                    let nestedRows = nestedTable.rows;
                    for (let k = 0; k < nestedRows.length; k++) {
                        let nestedCells = nestedRows[k].cells;
                        for (let m = 0; m < nestedCells.length; m++) {
                            row.push(nestedCells[m].innerText.trim()); // Extract nested table cell separately
                        }
                    }
                } else {
                    row.push(cell.innerText.trim()); // Extract normal table cell
                }
            }

            rows.push(row); // Add row data to the array
        }

        // Convert rows into a worksheet
        let worksheet = XLSX.utils.aoa_to_sheet(rows);

        // Adjust column widths for better readability
        worksheet['!cols'] = Array(rows[0].length).fill({
            wch: 20
        });

        // Create a workbook and add the worksheet
        let workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, "Attendance Report");

        // Export the workbook
        XLSX.writeFile(workbook, "attendance_report.xlsx");
    }
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
