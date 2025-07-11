<x-filament-panels::page>
    {{ $this->getTableFiltersForm() }}
    <style>
        /* ... يمكن إبقاء كل ستايلاتك كما هي ... */
    </style>

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
            <thead>
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
                        $rowspan = max(count($periods), 1);
                    @endphp

                    @if (count($periods) > 0)
                        @foreach ($periods as $index => $period)
                            <x-filament-tables::row>
                                @if ($index == 0)
                                    <x-filament-tables::cell style="display: {{ $show_day ? 'table-cell' : 'none' }};"
                                        rowspan="{{ $rowspan }}">
                                        {{ $data['day_name'] ?? ($data['day'] ?? '') }}
                                    </x-filament-tables::cell>
                                    <x-filament-tables::cell
                                        rowspan="{{ $rowspan }}">{{ $date }}</x-filament-tables::cell>
                                @endif
                                <x-filament-tables::cell
                                    class="internal_cell">{{ $period['start_time'] ?? ($period['start_at'] ?? '-') }}</x-filament-tables::cell>
                                <x-filament-tables::cell
                                    class="internal_cell">{{ $period['end_time'] ?? ($period['end_at'] ?? '-') }}</x-filament-tables::cell>
                                <x-filament-tables::cell class="internal_cell">-</x-filament-tables::cell>
                                <x-filament-tables::cell class="internal_cell">-</x-filament-tables::cell>
                                <x-filament-tables::cell class="internal_cell">-</x-filament-tables::cell>
                                <x-filament-tables::cell class="internal_cell">-</x-filament-tables::cell>
                                <x-filament-tables::cell class="internal_cell">-</x-filament-tables::cell>
                                <x-filament-tables::cell class="internal_cell">-</x-filament-tables::cell>
                                <x-filament-tables::cell class="internal_cell">-</x-filament-tables::cell>
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
            <tfoot>
                <x-filament-tables::row>
                    <th colspan="{{ $show_day ? 8 : 7 }}" class="text-right font-bold">{{ __('Total') }}</th>
                    <td class="text-center">-</td>
                    <td class="text-center">-</td>
                    <td class="text-center">-</td>
                </x-filament-tables::row>
            </tfoot>
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
</x-filament-panels::page>
