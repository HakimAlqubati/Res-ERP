<x-filament-panels::page>
    {{-- Filters form --}}
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

        .arrow-icon {
            margin-left: 5px;
            font-size: 14px;
        }

        .cursor-pointer {
            cursor: pointer;
        }

        .emp_name {
            padding: 0 3px 0 3px;
        }

        .star-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 16px;
            height: 16px;
            /* background-color: #ea580c; */
            /* Orange background */
            color: white;
            /* White star */
            border-radius: 50%;
            font-size: 10px;
            margin-right: 4px;
            vertical-align: middle;
        

        margin-right: 4px;
        vertical-align: middle;
        }

        /* Handle star badge on striped rows (assuming even rows are colored/green) */
        table tbody tr:nth-child(even) .star-badge {
            background-color: transparent;
            /* Star remains white (color: white) */
        }

        .pulsing-dot {
            display: inline-block;
            width: 6px;
            height: 6px;
            background-color: #ea580c;
            /* Orange for visibility */
            border-radius: 50%;
            margin-right: 4px;
            vertical-align: middle;
            animation: pulse-opacity 1.5s infinite ease-in-out;
        }

        @keyframes pulse-opacity {
            0% {
                opacity: 0.5;
                transform: scale(0.9);
            }

            50% {
                opacity: 1;
                transform: scale(1.2);
            }

            100% {
                opacity: 0.5;
                transform: scale(0.9);
            }
        }
    </style>

    @if (!empty($branch_id))
    <div class="text-right mb-4">
        <button onclick="printReport()" class="btn btn-print">
            &#128438; {{ __('Print Report') }}
        </button>
        <button onclick="exportToExcel()" class="btn btn-primary">
            &#128200; {{ __('Export to Excel') }}
        </button>
    </div>

    <table id="report-table" class="w-full text-sm text-left pretty reports">
        <thead class="fixed-header" style="padding-top: 5px;;top:64px;">
            <tr class="header_report">
                <th colspan="3"
                    class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }} company-info">
                    <div style="width: 100%;">
                        <img style="display: inline-block;"
                            src="{{ asset('/storage/' . setting('company_logo') . '') }}" alt="Company Logo"
                            class="logo-left">
                    </div>
                </th>
                <th colspan="4" class="no_border_right_left" style="text-align: center; vertical-align: middle;">
                    <div style="width: 100%;">
                        <p style="font-size: 16px; font-weight: bold; margin-bottom: 5px;">
                            {{ __('Attendance Report') }}
                        </p>
                        <p style="font-size: 14px; margin: 3px 0;">
                            (

                            {{ $branch_id == '' ? __('All Branches') : \App\Models\Branch::find($branch_id)?->name }}
                            )


                        </p>
                        <p style="font-size: 14px; margin: 3px 0;">
                            {{ $date ?? '-' }}
                        </p>
                    </div>
                </th>
                <th colspan="3" style="text-align: center; vertical-align: middle; padding:12px;"
                    class="{{ app()->getLocale() == 'en' ? 'no_border_left' : 'no_border_right' }}">
                    <img class="circle-image" src="{{ url('/') . '/' . 'storage/workbench.png' }}" alt="">
                </th>
            </tr>

            <tr class="fixed-header">
                <th rowspan="2">{{ __('Employee') }}</th>
                <th colspan="2">{{ __('Shift data') }}</th>

                <th colspan="4">{{ __('Check-in and Check-out data') }}</th>
                <th colspan="3">{{ __('Work Hours Summary') }}</th>

                {{-- <th rowspan="2">{{ __('Early departure (hour)') }}</th>
                <th rowspan="2">{{ __('Delay time (minute)') }}</th> --}}

            </tr>
            <tr class="fixed-header">
                <th class="internal_cell">{{ __('From') }}</th>
                <th class="internal_cell">{{ __('To') }}</th>
                <th class="internal_cell">{{ __('Check-in') }}</th>
                <!-- Sortable columns with up and down arrows -->
                <th class="internal_cell">
                    {{ __('Status') }}
                    <!-- <span id="checkin_status-arrow" class="arrow-icon">&#x2195;</span> Arrow for Check-in -->
                </th>
                <th class="internal_cell">{{ __('Check-out') }}</th>
                <th class="internal_cell">
                    {{ __('Status') }}
                    <!-- <span id="checkout_status-arrow" class="arrow-icon">&#x2195;</span> Arrow for Check-out -->
                </th>

                <th class="internal_cell">{{ __('Supposed') }}</th>
                <th class="internal_cell">{{ __('Total Hours Worked') }}</th>
                <th class="internal_cell">{{ __('Approved') }}</th>
            </tr>

        </thead>
        <tbody id="report-body">
            @forelse($employees as $employeeData)
            @php
            $emp = $employeeData['employee'];
            $att = $employeeData['attendance_report'][$date] ?? [];
            $periods = $att['periods'] ?? [];
            $status = $att['day_status'] ?? null;
            @endphp

            <tr>
                <td>
                    <p class="emp_name">{{ $emp['name'] }}</p>
                </td>
                @if ($status === 'leave')
                <td colspan="9" class="text-center text-gray-500 font-bold">
                    {{ $att['leave_type'] ?? __('Leave') }}
                </td>
                @elseif(empty($periods))
                <td colspan="9" class="text-center text-gray-500 font-bold">
                    {{ __('No periods') }}
                </td>
                @else
                <td colspan="9" style="padding:0;">
                    <table style="width:100%; border:none;">

                        @foreach ($periods as $item)
                        @php
                        $checkin = $item['attendances']['checkin']['0'] ?? null;
                        $checkout = $item['attendances']['checkout']['0'] ?? null;
                        $lastcheckout = $item['attendances']['checkout']['lastcheckout'] ?? null;
                        @endphp
                        <tr>
                            @if ($item['final_status'] == 'absent' )
                            <td class="internal_cell">{{ $item['start_time'] ?? '-' }}</td>
                            <td class="internal_cell">{{ $item['end_time'] ?? '-' }}</td>
                            <td colspan="7" class="text-center">{{ __('Absent') }}</td>
                            @elseif ($item['final_status'] == 'future')
                            <td colspan="7" class="internal_cell">
                                <p>-</p> {{ '' }}
                            </td>
                            @else
                            <td
                                class="internal_cell">{{ $item['start_time'] ?? '-' }}</td>
                            <td
                                class="internal_cell">{{ $item['end_time'] ?? '-' }}</td>
                            <td
                                class="internal_cell">{{ $checkin['check_time'] ?? '-' }}</td>
                            <td
                                class="internal_cell">

                                {{$checkin['status_label']??'-'}}
                            </td>
                            <td
                                class="internal_cell">{{ $lastcheckout['check_time'] ?? '-' }}</td>
                            <td
                                class="internal_cell">

                                {{$checkout['status_label']??'-'}}
                            </td>

                            <td
                                class="internal_cell">{{ $lastcheckout['supposed_duration_hourly'] ?? '-' }}</td>
                            <td class="internal_cell">
                                @php
                                $duration = $lastcheckout['total_actual_duration_hourly'] ?? '-';
                                @endphp
                                @if ($duration !== '-')
                                <button
                                    class="text-blue-600 font-semibold underline hover:text-blue-900 transition"
                                    wire:click="showDetails('{{ $date }}', {{ $emp['id'] }}, {{ $item['period_id'] }})"
                                    style="cursor:pointer; border:none; background:none; padding:0;"
                                    title="Show all check-in/out details">
                                    <span class="star-badge">&#9733;</span> {{ $duration }}
                                </button>
                                @else
                                <span>{{ $duration }}</span>
                                @endif
                            </td>
                            <td
                                class="internal_cell">{{ $lastcheckout['approved_overtime'] ?? '-' }}</td>
                            @endif
                        </tr>
                        @endforeach

                    </table>
                </td>
                @endif
            </tr>
            @empty
            <tr>
                <td colspan="10" class="text-center">
                    {{ __('No attendance data found for the selected date.') }}
                </td>
            </tr>
            @endforelse
        </tbody>
        {{-- يمكنك إضافة tfoot للمجاميع إذا أردت --}}
    </table>

    {{-- Scripts --}}
    <script>
        function printReport() {
            const printButton = document.querySelector('button[onclick="printReport()"]');
            if (printButton) printButton.style.display = 'none';
            window.print();
            if (printButton) printButton.style.display = 'inline-flex';
        }

        function exportToExcel() {
            let table = document.getElementById("report-table");
            let rows = [];
            for (let i = 0; i < table.rows.length; i++) {
                let row = [];
                let cells = table.rows[i].cells;
                for (let j = 0; j < cells.length; j++) {
                    let cell = cells[j];
                    let nestedTable = cell.querySelector("table");
                    if (nestedTable) {
                        let nestedRows = nestedTable.rows;
                        for (let k = 0; k < nestedRows.length; k++) {
                            let nestedCells = nestedRows[k].cells;
                            for (let m = 0; m < nestedCells.length; m++) {
                                row.push(nestedCells[m].innerText.trim());
                            }
                        }
                    } else {
                        row.push(cell.innerText.trim());
                    }
                }
                rows.push(row);
            }
            let worksheet = XLSX.utils.aoa_to_sheet(rows);
            worksheet['!cols'] = Array(rows[0].length).fill({
                wch: 20
            });
            let workbook = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(workbook, worksheet, "Attendance Report");
            XLSX.writeFile(workbook, "attendance_report.xlsx");
        }
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    @else
    <div class="please_select_message_div" style="text-align: center;">
        <h1 class="please_select_message_text">{{ __('Please select a Branch') }}</h1>
    </div>
    @endif

    @if ($showDetailsModal)
    @include('components.hr.attendances-reports.attendance-details-modal', [
    'modalData' => $modalData,
    ])
    @endif
</x-filament-panels::page>