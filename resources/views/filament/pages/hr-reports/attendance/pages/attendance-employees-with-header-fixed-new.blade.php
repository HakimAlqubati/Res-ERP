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
    </style>

    <div class="text-right mb-4">
        <button onclick="printReport()" class="btn btn-print">
            &#128438; {{ __('Print Report') }}
        </button>
        <button onclick="exportToExcel()" class="btn btn-primary">
            &#128200; {{ __('Export to Excel') }}
        </button>
    </div>

    <x-filament-tables::table id="report-table" class="w-full text-sm text-left pretty reports">
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
                <!-- Sortable columns with up and down arrows -->
                <th class="internal_cell cursor-pointer" onclick="sortTable('checkin_status')">
                    {{ __('Status') }}
                    <span id="checkin_status-arrow" class="arrow-icon">&#x2195;</span> <!-- Arrow for Check-in -->
                </th>
                <th class="internal_cell">{{ __('Check-out') }}</th>
                <th class="internal_cell cursor-pointer" onclick="sortTable('checkout_status')">
                    {{ __('Status') }}
                    <span id="checkout_status-arrow" class="arrow-icon">&#x2195;</span> <!-- Arrow for Check-out -->
                </th>

                <th class="internal_cell">{{ __('Supposed') }}</th>
                <th class="internal_cell">{{ __('Total Hours Worked') }}</th>
                <th class="internal_cell">{{ __('Approved') }}</th>
            </x-filament-tables::row>

        </thead>
        <tbody id="report-body">
            @forelse($employees as $employeeData)
                @php
                    $emp = $employeeData['employee'];
                    $att = $employeeData['attendance_report'][$date] ?? [];
                    $periods = $att['periods'] ?? [];
                    $status = $att['day_status'] ?? null;
                @endphp

                <x-filament-tables::row>
                    <x-filament-tables::cell>
                        <strong>{{ $emp['name'] }}</strong>
                    </x-filament-tables::cell>
                    @if ($status === 'leave')
                        <x-filament-tables::cell colspan="9" class="text-center text-gray-500 font-bold">
                            {{ $att['leave_type'] ?? __('Leave') }}
                        </x-filament-tables::cell>
                    @elseif(empty($periods))
                        <x-filament-tables::cell colspan="9" class="text-center text-gray-500 font-bold">
                            {{ __('No work period / Absent') }}
                        </x-filament-tables::cell>
                    @else
                        <x-filament-tables::cell colspan="9" style="padding:0;">
                            <x-filament-tables::table style="width:100%; border:none;">

                                @foreach ($periods as $item)
                                    @php
                                        $checkin = $item['attendances']['checkin']['0'] ?? null;
                                        $checkout = $item['attendances']['checkout']['0'] ?? null;
                                        $lastcheckout = $item['attendances']['checkout']['lastcheckout'] ?? null;
                                    @endphp
                                    <x-filament-tables::row>
                                        @if ($item['final_status'] == 'absent')
                                       <x-filament-tables::cell colspan="7">{{'Absent'}} </x-filament-tables::cell>
                                        @else
                                            <x-filament-tables::cell
                                                class="internal_cell">{{ $item['start_time'] ?? '-' }}</x-filament-tables::cell>
                                            <x-filament-tables::cell
                                                class="internal_cell">{{ $item['end_time'] ?? '-' }}</x-filament-tables::cell>
                                            <x-filament-tables::cell
                                                class="internal_cell">{{ $checkin['check_time'] ?? '-' }}</x-filament-tables::cell>
                                            <x-filament-tables::cell
                                                class="internal_cell">{{ $checkin['status'] ?? '-' }}</x-filament-tables::cell>
                                            <x-filament-tables::cell
                                                class="internal_cell">{{ $lastcheckout['check_time'] ?? '-' }}</x-filament-tables::cell>
                                            <x-filament-tables::cell
                                                class="internal_cell">{{ $checkout['status'] ?? '-' }}</x-filament-tables::cell>

                                            <x-filament-tables::cell
                                                class="internal_cell">{{ $lastcheckout['supposed_duration_hourly'] ?? '-' }}</x-filament-tables::cell>
                                            <x-filament-tables::cell
                                                class="internal_cell">{{ $lastcheckout['actual_duration_hourly'] ?? '-' }}</x-filament-tables::cell>
                                            <x-filament-tables::cell
                                                class="internal_cell">{{ $lastcheckout['approved_overtime'] ?? '-' }}</x-filament-tables::cell>
                                        @endif
                                    </x-filament-tables::row>
                                @endforeach

                            </x-filament-tables::table>
                        </x-filament-tables::cell>
                    @endif
                </x-filament-tables::row>
            @empty
                <x-filament-tables::row>
                    <x-filament-tables::cell colspan="10" class="text-center">
                        {{ __('No attendance data found for the selected date.') }}
                    </x-filament-tables::cell>
                </x-filament-tables::row>
            @endforelse
        </tbody>
        {{-- يمكنك إضافة tfoot للمجاميع إذا أردت --}}
    </x-filament-tables::table>

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
</x-filament-panels::page>
