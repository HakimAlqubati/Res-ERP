<x-filament-panels::page>
    {{ $this->getTableFiltersForm() }}

    <style>
        .overtime-report-table {
            width: 100%;
            border-collapse: collapse;
        }

        .overtime-report-table th,
        .overtime-report-table td {
            padding: 10px 14px;
            text-align: center;
            border-bottom: 1px solid rgba(128, 128, 128, 0.2);
        }

        .overtime-report-table thead th {
            font-weight: 600;
            font-size: 13px;
        }

        .overtime-report-table tbody tr:hover {
            background-color: rgba(128, 128, 128, 0.05);
        }

        .overtime-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }

        .overtime-summary-card {
            padding: 16px;
            border-radius: 12px;
            text-align: center;
            border: 1px solid rgba(128, 128, 128, 0.15);
        }

        .overtime-summary-card .value {
            font-size: 24px;
            font-weight: 700;
            line-height: 1.2;
        }

        .overtime-summary-card .label {
            font-size: 12px;
            opacity: 0.7;
            margin-top: 4px;
        }

        .badge-approved {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            background-color: #dcfce7;
            color: #166534;
        }

        .badge-pending {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            background-color: #fef9c3;
            color: #854d0e;
        }

        .badge-rejected {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            background-color: #fee2e2;
            color: #991b1b;
        }

        .btn-export {
            border: 1px solid #22c55e;
            border-radius: 6px;
            padding: 6px 16px;
            background-color: #22c55e;
            color: white;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
        }

        .btn-export:hover {
            background-color: #16a34a;
        }

        @media print {
            body * {
                visibility: hidden;
            }

            #overtime-report-table,
            #overtime-report-table * {
                visibility: visible;
            }

            #overtime-report-table {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
            }

            .overtime-report-table th,
            .overtime-report-table td {
                border: 1px solid #000;
                color: #000;
            }
        }
    </style>

    {{-- Summary Cards --}}
    @if ($summary['total_records'] > 0)
    <div class="overtime-summary-grid">
        <div class="overtime-summary-card">
            <div class="value">{{ $summary['total_records'] }}</div>
            <div class="label">{{ __('lang.total_records') }}</div>
        </div>
        <div class="overtime-summary-card">
            <div class="value">{{ $summary['total_hours'] }}</div>
            <div class="label">{{ __('lang.total_hours') }}</div>
        </div>
        <div class="overtime-summary-card">
            <div class="value">{{ $summary['approved_count'] }}</div>
            <div class="label">{{ __('lang.approved') }}</div>
        </div>
        <div class="overtime-summary-card">
            <div class="value">{{ $summary['pending_count'] }}</div>
            <div class="label">{{ __('lang.pending') }}</div>
        </div>
        <div class="overtime-summary-card">
            <div class="value">{{ $summary['rejected_count'] }}</div>
            <div class="label">{{ __('lang.rejected') }}</div>
        </div>
        <div class="overtime-summary-card">
            <div class="value">{{ $summary['unique_employees'] }}</div>
            <div class="label">{{ __('lang.employees') }}</div>
        </div>
    </div>

    {{-- Actions --}}
    <div style="display: flex; gap: 8px; margin-bottom: 16px;">
        <button onclick="exportOvertimeToExcel()" class="btn-export">
            &#128200; {{ __('lang.to_excel') }}
        </button>
        <button onclick="window.print()" class="btn-export" style="background-color: #3b82f6; border-color: #3b82f6;">
            &#128438; {{ __('lang.print') }}
        </button>
    </div>

    {{-- Report Table --}}
    <table class="overtime-report-table w-full text-sm" id="overtime-report-table">
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('lang.employee') }}</th>
                <th>{{ __('lang.branch') }}</th>
                <th>{{ __('lang.date') }}</th>
                <th>{{ __('lang.start_time') }}</th>
                <th>{{ __('lang.end_time') }}</th>
                <th>{{ __('lang.hours') }}</th>
                <th>{{ __('lang.status') }}</th>
                <th>{{ __('lang.approved_by') }}</th>
                <th>{{ __('lang.notes') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->employee->name ?? '-' }}</td>
                <td>{{ $item->employee->branch->name ?? '-' }}</td>
                <td>{{ $item->date }}</td>
                <td>{{ $item->start_time ?? '-' }}</td>
                <td>{{ $item->end_time ?? '-' }}</td>
                <td>{{ $item->hours }}</td>
                <td>
                    <span class="{{ $item->status_badge_class }}">{{ $item->status_label }}</span>
                </td>
                <td>{{ $item->approvedBy->name ?? '-' }}</td>
                <td>{{ $item->notes ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight: 700;">
                <td colspan="6">{{ __('lang.total') }}</td>
                <td>{{ $summary['total_hours'] }}</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>
    @else
    <div style="text-align: center; padding: 40px 0;">
        <p style="font-size: 16px; opacity: 0.6;">{{ __('lang.no_data') }}</p>
    </div>
    @endif

    {{-- Excel Export Script --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        function exportOvertimeToExcel() {
            var elt = document.getElementById('overtime-report-table');
            var clone = elt.cloneNode(true);
            var wb = XLSX.utils.table_to_sheet(clone, {
                raw: true
            });

            var wscols = [];
            for (var i = 0; i < 10; i++) {
                wscols.push({
                    wch: 18
                });
            }
            wb['!cols'] = wscols;

            var workbook = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(workbook, wb, "Overtime Report");
            XLSX.writeFile(workbook, "overtime_report.xlsx");
        }
    </script>
</x-filament-panels::page>