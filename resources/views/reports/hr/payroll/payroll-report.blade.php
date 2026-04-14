<x-filament-panels::page>
    {{ $this->getTableFiltersForm() }}

    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }

        /* Print-specific styles */
        @media print {
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

            table {
                border-collapse: collapse;
                width: 100%;
            }

            th,
            td {
                border: 1px solid #000;
                padding: 10px;
                font-size: 10px;
                color: #000;
            }

            th {
                background-color: #ddd;
            }
        }

        .btn-primary {
            border: 1px solid green;
            border-radius: 5px;
            padding: 0px 10px;
            min-width: 150px;
        }

        .pretty th,
        .pretty td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }

        .pretty thead th {
            color: #0b7a5a;
            font-weight: bold;
            background-color: #fff;
            white-space: nowrap;
        }

        .pretty tbody tr:nth-child(odd) td {
            background-color: #f0fdf4;
            color: #111827;
        }

        .pretty tbody tr:nth-child(even) td {
            background-color: #ffffff;
            color: #111827;
        }

        .pretty tfoot td {
            background-color: #eaeaea;
            color: #111827;
            font-weight: bold;
            position: sticky;
            bottom: 0;
            z-index: 20;
            box-shadow: 0 -4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .fixed-header th {
            position: sticky;
            z-index: 20;
            background-color: #fff;
            border-bottom: 1px solid #ddd !important;
        }

        .header_report th {
            top: 58px;
            padding: 8px 16px !important;
        }

        .pretty thead tr:nth-child(2) th {
            top: 124px; /* Flush against first row */
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
    </style>

    @if ($reportData && count($reportData->items) > 0)
    @php
        $displayName = $branchName ? $branchName . ' - ' . __('Payroll Report') : __('Payroll Report');
    @endphp

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left pretty" id="report-table">
            <thead class="fixed-header">
                <tr class="header_report">
                    <th colspan="11" style="padding: 12px 16px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px;">
                            <div style="display: flex; flex-direction: column; gap: 6px; flex-shrink: 0;">
                                <button onclick="exportToExcel()" class="btn btn-primary">
                                    &#128200; {{ __('Export Excel') }}
                                </button>
                            </div>

                            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; flex: 1;">
                                <span style="font-size: 16px; font-weight: bold;">{{ $displayName }}</span>
                                <span style="font-size: 13px; font-weight: 600; color: #666;">{{ __('Period') . ': ' . $period }}</span>
                            </div>

                            <div style="flex-shrink: 0; text-align: right;">
                                <img src="{{ url('/storage/workbench.png') }}" alt="Logo" style="width: 50px; height: 50px; object-fit: contain;">
                            </div>
                        </div>
                    </th>
                </tr>
                <tr>
                    <th>{{ __('Employee') }}</th>
                    <th>{{ __('Basic Salary') }}</th>
                    <th>{{ __('Allowances') }}</th>
                    <th>{{ __('Overtime') }}</th>
                    <th>{{ __('Bonus') }}</th>
                    <th>{{ __('Gross Salary') }}</th>
                    <th>{{ __('Deductions') }}</th>
                    <th>{{ __('Penalties') }}</th>
                    <th>{{ __('Advances') }}</th>
                    <th>{{ __('Net Salary') }}</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($reportData->items as $item)
                <tr>
                    <td style="text-align: left; font-weight: 500;">{{ $item->employeeName }}</td>
                    <td>{{ formatMoneyWithCurrency($item->baseSalary) }}</td>
                    <td>{{ formatMoneyWithCurrency($item->totalAllowances) }}</td>
                    <td>{{ formatMoneyWithCurrency($item->totalOvertime) }}</td>
                    <td>{{ formatMoneyWithCurrency($item->totalBonus) }}</td>
                    <td style="font-weight: bold; color: #0b7a5a;">{{ formatMoneyWithCurrency($item->grossSalary) }}</td>
                    <td>{{ formatMoneyWithCurrency($item->totalDeductions) }}</td>
                    <td>{{ formatMoneyWithCurrency($item->totalPenalties) }}</td>
                    <td>{{ formatMoneyWithCurrency($item->totalAdvances) }}</td>
                    <td style="font-weight: bold; background-color: #d1fae5 !important;">{{ formatMoneyWithCurrency($item->netSalary) }}</td>
                </tr>
                @endforeach
            </tbody>

            <tfoot>
                <tr>
                    <td style="text-align: right;">{{ __('GRAND TOTAL') }}</td>
                    <td>{{ formatMoneyWithCurrency($reportData->grandTotalBaseSalary) }}</td>
                    <td>{{ formatMoneyWithCurrency($reportData->grandTotalAllowances) }}</td>
                    <td>{{ formatMoneyWithCurrency($reportData->grandTotalOvertime) }}</td>
                    <td>{{ formatMoneyWithCurrency($reportData->grandTotalBonus) }}</td>
                    <td>{{ formatMoneyWithCurrency($reportData->grandTotalGrossSalary) }}</td>
                    <td>{{ formatMoneyWithCurrency($reportData->grandTotalDeductions) }}</td>
                    <td>{{ formatMoneyWithCurrency($reportData->grandTotalPenalties) }}</td>
                    <td>{{ formatMoneyWithCurrency($reportData->grandTotalAdvances) }}</td>
                    <td style="color: #d9534f;">{{ formatMoneyWithCurrency($reportData->grandTotalNetSalary) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    @else
    <div class="please_select_message_div" style="text-align: center; padding: 60px;">
        <div class="flex flex-col items-center justify-center space-y-4">
            <h1 class="please_select_message_text" style="font-size: 1.25rem; color: #6b7280;">
                {{ $branchId && $period ? __('No payroll records found for the selected period.') : __('Please select a Branch and a Month') }}
            </h1>
        </div>
    </div>
    @endif

    {{-- Excel Export Script --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        function exportToExcel() {
            var elt = document.getElementById('report-table');
            var clone = elt.cloneNode(true);

            // Clean buttons before export
            var buttons = clone.querySelectorAll('button');
            buttons.forEach(function(btn) {
                btn.remove();
            });

            var wb = XLSX.utils.table_to_sheet(clone, {raw: true});
            var workbook = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(workbook, wb, "Payroll Report");
            
            var branchName = "{{ $branchName ? preg_replace('/[^A-Za-z0-9_\-]/', '_', $branchName) : 'All' }}";
            var period = "{{ preg_replace('/[^A-Za-z0-9_\-]/', '_', $period) }}";
            var fileName = "Payroll_Report_" + branchName + "_" + period + ".xlsx";
            
            XLSX.writeFile(workbook, fileName);
        }
    </script>
</x-filament-panels::page>
