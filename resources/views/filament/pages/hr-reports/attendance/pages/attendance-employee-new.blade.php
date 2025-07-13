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
    <div class="text-right mb-4">
        <button type="button" class="btn btn-info" onclick="showChartModal()">
            📊 {{ __('Show Charts') }}
        </button>
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
                    <th colspan="4">
                        <p>({{ \App\Models\Employee::find($employee_id)?->name ?? __('lang.choose_branch') }})</p>
                    </th>
                    <th colspan="2">
                        <p>{{ __('lang.start_date') . ': ' . $start_date }}</p>
                        <br>
                        <p>{{ __('lang.end_date') . ': ' . $end_date }}</p>
                    </th>
                    <th colspan="5" style="text-align: center;">
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
                @foreach ($report_data as $date => $data)
                    @php
                        $periods = $data['periods'] ?? [];
                        $dayStatus = $data['day_status'] ?? null;

                    @endphp

                    @if ($dayStatus == 'leave')
                        <x-filament-tables::row>
                            @if ($show_day)
                                <x-filament-tables::cell>{{ $data['day_name'] ?? ($data['day'] ?? '') }}</x-filament-tables::cell>
                            @endif
                            <x-filament-tables::cell>{{ $date }}</x-filament-tables::cell>
                            <x-filament-tables::cell colspan="9" class="text-center text-gray-500 font-bold">
                                {{ $data['leave_type'] }}
                            </x-filament-tables::cell>
                        </x-filament-tables::row>
                    @elseif (!is_null($dayStatus) && count($periods) > 0)
                        {{-- @if (count($periods) > 0) --}}
                        @foreach ($periods as $periodIndex => $period)
                            @php
                                // احصل على كل checkins الرقمية
                                $checkIns = collect($period['attendances']['checkin'] ?? [])
                                    ->filter(fn($v, $k) => is_int($k))
                                    ->values()
                                    ->all();
                                // أول دخول
                                $firstCheckin = $checkIns[0]['check_time'] ?? '-';
                                $firstCheckinStatus = $checkIns[0]['status'] ?? '-';

                                // آخر خروج (باستخدام lastcheckout حصراً)
                                $lastCheckout = $period['attendances']['checkout']['lastcheckout']['check_time'] ?? '-';
                                $lastCheckoutStatus =
                                    $period['attendances']['checkout']['lastcheckout']['status'] ?? '-';
                            @endphp

                            <x-filament-tables::row>
                                {{-- اليوم --}}
                                @if ($periodIndex == 0)
                                    @if ($show_day)
                                        <x-filament-tables::cell rowspan="{{ count($periods) }}">
                                            {{ $data['day_name'] ?? ($data['day'] ?? '') }}
                                        </x-filament-tables::cell>
                                    @endif
                                    <x-filament-tables::cell rowspan="{{ count($periods) }}">
                                        {{ $date }}
                                    </x-filament-tables::cell>
                                @endif

                                {{-- بيانات الفترة --}}
                                <x-filament-tables::cell>
                                    {{ $period['start_time'] ?? '-' }}
                                </x-filament-tables::cell>
                                <x-filament-tables::cell>
                                    {{ $period['end_time'] ?? '-' }}
                                </x-filament-tables::cell>

                                @if ($period['final_status'] == 'absent')
                                    <x-filament-tables::cell colspan="8">
                                        Absent
                                    </x-filament-tables::cell>
                                @else
                                    <x-filament-tables::cell>
                                        {{ $firstCheckin }}
                                    </x-filament-tables::cell>

                                    <x-filament-tables::cell>
                                        {{ $firstCheckinStatus }}
                                    </x-filament-tables::cell>

                                    <x-filament-tables::cell>
                                        {{ $lastCheckout }}
                                    </x-filament-tables::cell>

                                    <x-filament-tables::cell>
                                        {{ $lastCheckoutStatus }}
                                    </x-filament-tables::cell>

                                    <x-filament-tables::cell>
                                        {{ $period['attendances']['checkout']['lastcheckout']['supposed_duration_hourly'] ?? '-' }}

                                    </x-filament-tables::cell>
                                    <x-filament-tables::cell>
                                        @php
                                            $duration =
                                                $period['attendances']['checkout']['lastcheckout'][
                                                    'total_actual_duration_hourly'
                                                ] ?? '-';
                                        @endphp
                                        @if ($duration !== '-')
                                            <button
                                                class="text-blue-600 font-semibold underline hover:text-blue-900 transition"
                                                wire:click="showDetails('{{ $date }}', {{ $employee_id }}, {{ $period['period_id'] }})"
                                                style="cursor:pointer; border:none; background:none; padding:0;"
                                                title="Show all check-in/out details">
                                                {{ $duration }}
                                            </button>
                                        @else
                                            <span>{{ $duration }}</span>
                                        @endif
                                    </x-filament-tables::cell>
                                    <x-filament-tables::cell>
                                        {{ $period['attendances']['checkout']['lastcheckout']['approved_overtime'] ?? '-' }}
                                    </x-filament-tables::cell>
                                @endif
                            </x-filament-tables::row>
                        @endforeach
                    @else
                        <x-filament-tables::row>
                            @if ($show_day)
                                <x-filament-tables::cell>{{ $data['day_name'] ?? ($data['day'] ?? '') }}</x-filament-tables::cell>
                            @endif
                            <x-filament-tables::cell>{{ $date }}</x-filament-tables::cell>
                            <x-filament-tables::cell colspan="9" class="text-center text-gray-500 font-bold">
                                {{ __('No periods') }}
                            </x-filament-tables::cell>
                        </x-filament-tables::row>
                    @endif
                @endforeach
            </tbody>

            {{-- <tfoot>
                <x-filament-tables::row>
                    <th colspan="{{ $show_day ? 8 : 7 }}" class="text-right font-bold">{{ __('Total') }}</th>
                    <td class="text-center">-</td>
                    <td class="text-center">-</td>
                    <td class="text-center">-</td>
                </x-filament-tables::row>
            </tfoot> --}}
        </x-filament-tables::table>
    @else
        <div class="please_select_message_div" style="text-align: center;">
            <h1 class="please_select_message_text">{{ __('Please select an Employee') }}</h1>
        </div>
    @endif

    {{-- نفس سكريبتات الطباعة والتصدير --}}
    <script>
        function printReport() {
            const printButton = document.querySelector('button[onclick="printReport()"]');
            if (printButton) printButton.style.display = 'none';
            window.print();
            if (printButton) printButton.style.display = 'block';
        }
    </script>
    <script>
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
    @if ($showDetailsModal)
        @include('components.hr.attendances-reports.attendance-details-modal', [
            'modalData' => $modalData,
        ])
    @endif

    @include('components.hr.attendances-reports.attendance-stats-chart-modal', [
        'chartData' => $chartData,
    ])
</x-filament-panels::page>
