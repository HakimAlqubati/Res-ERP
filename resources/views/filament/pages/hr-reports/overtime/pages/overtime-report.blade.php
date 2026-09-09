<x-filament-panels::page>
    {{ $this->getTableFiltersForm() }}

    <style>
        /* Base Table Styling - Matching Attendance Report */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        #overtime-report-table {
            table-layout: fixed;
            width: {{ $branch_id ? 'clamp(860px, 88vw, 980px)' : 'clamp(980px, 92vw, 1160px)' }};
            min-width: {{ $branch_id ? '860px' : '980px' }};
            max-width: none;
        }

        #overtime-report-table .col-staff { width: clamp(170px, 18vw, 230px); }
        #overtime-report-table .col-branch { width: clamp(130px, 12vw, 170px); }
        #overtime-report-table .col-date { width: 8rem; }
        #overtime-report-table .col-time { width: 6.3rem; }
        #overtime-report-table .col-hours { width: 4.5rem; }
        #overtime-report-table .col-status { width: 8rem; }
        #overtime-report-table .col-approved { width: clamp(180px, 20vw, 260px); }

        #overtime-report-table th,
        #overtime-report-table td {
            overflow: hidden;
            text-overflow: ellipsis;
        }

        #overtime-report-table td {
            white-space: nowrap;
        }

        #overtime-report-table td.text-left {
            white-space: normal;
            word-break: break-word;
        }

        .header_report {
            background: white !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .header_report th {
            padding: 10px 12px !important;
            border-bottom: 1px solid #e5e7eb !important;
        }

        .column_headers th {
            position: sticky;
            top: 0;
            background-color: #f3f4f6 !important;
            color: #374151;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            padding: 7px 10px !important;
            border-bottom: 2px solid #d1d5db !important;
            z-index: 30;
            font-size: 11px;
        }

        .pretty.reports tbody tr:nth-child(even) {
            background-color: #0d7c66;
            color: white !important;
        }

        .pretty.reports tbody tr:nth-child(even) td {
            color: white !important;
        }

        .pretty.reports tbody tr:nth-child(even) .font-medium,
        .pretty.reports tbody tr:nth-child(even) .font-bold {
            color: white !important;
        }

        .pretty.reports tbody td {
            padding: 8px 12px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 12px;
            line-height: 1.25;
            vertical-align: middle;
        }

        .text-center { text-align: center !important; }
        .text-left { text-align: left !important; }
        .text-right { text-align: right !important; }

        .pretty.reports tfoot td {
            padding: 7px 10px;
            font-size: 12px;
            line-height: 1.25;
        }

        /* Buttons Styling */
        .btn-print,
        .btn-primary,
        .btn-secondary {
            border-radius: 7px;
            padding: 7px 12px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .btn-primary {
            background-color: #10b981;
            color: white;
            border: 1px solid #059669;
        }

        .btn-primary:hover {
            background-color: #059669;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn-secondary {
            background-color: #3b82f6;
            color: white;
            border: 1px solid #2563eb;
        }

        .btn-secondary:hover {
            background-color: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Status Badges */
        .badge-approved {
            background-color: #dcfce7;
            color: #166534;
            padding: 3px 9px;
            border-radius: 9999px;
            font-size: 10px;
            font-weight: 700;
            border: 1px solid #bbf7d0;
        }

        .badge-pending {
            background-color: #fef9c3;
            color: #854d0e;
            padding: 3px 9px;
            border-radius: 9999px;
            font-size: 10px;
            font-weight: 700;
            border: 1px solid #fef08a;
        }

        .badge-rejected {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 3px 9px;
            border-radius: 9999px;
            font-size: 10px;
            font-weight: 700;
            border: 1px solid #fecaca;
        }

        /* Summary Grid */
        .overtime-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .overtime-summary-card {
            background: white;
            padding: 24px;
            border-radius: 16px;
            text-align: center;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: transform 0.2s;
        }

        .overtime-summary-card:hover {
            transform: translateY(-2px);
        }

        .overtime-summary-card .value {
            font-size: 28px;
            font-weight: 800;
            color: #111827;
            line-height: 1;
        }

        .overtime-summary-card .label {
            font-size: 12px;
            color: #6b7280;
            margin-top: 8px;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.025em;
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

            .no-print {
                display: none !important;
            }

            .pretty.reports thead {
                position: static;
            }
        }
    </style>

    {{-- Report Content --}}
    @if ($summary['total_records'] > 0)
    <div class="overflow-x-auto bg-white rounded-2xl border border-gray-200 shadow-xl overflow-hidden">
        <table class="w-full text-xs text-left pretty reports" id="overtime-report-table">
            <colgroup>
                <col class="col-staff">
                @if (!$branch_id)
                    <col class="col-branch">
                @endif
                <col class="col-date">
                <col class="col-time">
                <col class="col-time">
                <col class="col-hours">
                <col class="col-status">
                <col class="col-approved">
            </colgroup>
            <thead>
                <tr class="header_report">
                    <th colspan="{{ $branch_id ? 7 : 8 }}">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px;">
                            {{-- Left: Actions --}}
                            <div style="display: flex; flex-direction: column; gap: 6px;" class="no-print">
                                <button onclick="exportOvertimeToExcel()" class="btn btn-primary" style="min-width: 112px;">
                                    <span style="font-size: 14px;">📊</span> {{ __('lang.to_excel') }}
                                </button>
                                <button onclick="window.print()" class="btn btn-secondary" style="min-width: 112px;">
                                    <span style="font-size: 14px;">🖨️</span> {{ __('lang.print') }}
                                </button>
                            </div>

                            {{-- Center: Report Context --}}
                            <div style="display: flex; align-items: center; justify-content: center; gap: 12px; flex: 1;">
                                @if ($employee)
                                <img src="{{ $employee->avatar_image }}"
                                    alt="{{ $employee->name }}"
                                    style="width: 58px; height: 58px; border-radius: 12px; object-fit: cover; border: 3px solid #fff; box-shadow: 0 3px 10px rgba(0,0,0,0.10);">
                                @endif
                                <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 2px; text-align: left; line-height: 1.25;">
                                    <span style="font-size: 15px; font-weight: 800; color: #111827;">
                                        {{ __('lang.overtime_report') }}
                                    </span>
                                    <span style="font-size: 12px; font-weight: 700; color: #374151;">
                                        {{ __('lang.branch') }}:
                                        @if($employee)
                                        {{ $employee->branch->name ?? '-' }}
                                        @elseif($branch_name && $branch_name !== '-')
                                        {{ $branch_name }}
                                        @else
                                        {{ __('lang.all_branches') ?? 'All Branches' }}
                                        @endif
                                    </span>
                                    <span style="font-size: 11px; color: #6b7280; font-weight: 600;">
                                        {{ __('lang.start_date') }}: {{ $start_date }}
                                    </span>
                                    <span style="font-size: 11px; color: #6b7280; font-weight: 600;">
                                        {{ __('lang.end_date') }}: {{ $end_date }}
                                    </span>
                                    @if($employee)
                                    <span style="font-size: 11px; color: #4b5563; font-weight: 600;">
                                        {{ __('lang.employee') }}:
                                        <a href="{{ \App\Filament\Resources\EmployeeResource::getUrl('view', ['record' => $employee->id]) }}" target="_blank" class="hover:text-emerald-600 transition-colors">
                                            {{ $employee->name }}
                                        </a>
                                    </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Right: Logo --}}
                            <div style="flex-shrink: 0; padding-left: 8px;">
                                <img src="{{ url('/') . '/' . 'storage/workbench.png' }}" alt="Logo" style="height: 46px; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.05));">
                            </div>
                        </div>
                    </th>
                </tr>

                {{-- Summary Cards Row --}}
                <tr class="summary_row no-print">
                    <th colspan="{{ $branch_id ? 7 : 8 }}" style="padding: 0 12px 10px 12px; background: white;">
                        <div class="overtime-summary-grid" style="margin-bottom: 0; gap: 8px;">
                            <div class="overtime-summary-card" style="padding: 8px; border-radius: 10px;">
                                <div class="value" style="font-size: 16px;">{{ $summary['total_records'] }}</div>
                                <div class="label" style="font-size: 9px; margin-top: 3px;">{{ __('lang.total_records') }}</div>
                            </div>
                            <div class="overtime-summary-card" style="padding: 8px; border-radius: 10px;">
                                <div class="value" style="font-size: 16px;">{{ $summary['total_hours'] }}</div>
                                <div class="label" style="font-size: 9px; margin-top: 3px;">{{ __('lang.total_hours') }}</div>
                            </div>
                            <div class="overtime-summary-card" style="padding: 8px; border-radius: 10px;">
                                <div class="value" style="font-size: 16px;">{{ $summary['approved_count'] }}</div>
                                <div class="label" style="font-size: 9px; margin-top: 3px;">{{ __('lang.approved') }}</div>
                            </div>
                            <div class="overtime-summary-card" style="padding: 8px; border-radius: 10px;">
                                <div class="value" style="font-size: 16px;">{{ $summary['pending_count'] }}</div>
                                <div class="label" style="font-size: 9px; margin-top: 3px;">{{ __('lang.pending') }}</div>
                            </div>
                            <div class="overtime-summary-card" style="padding: 8px; border-radius: 10px;">
                                <div class="value" style="font-size: 16px;">{{ $summary['rejected_count'] }}</div>
                                <div class="label" style="font-size: 9px; margin-top: 3px;">{{ __('lang.rejected') }}</div>
                            </div>
                            <div class="overtime-summary-card" style="padding: 8px; border-radius: 10px;">
                                <div class="value" style="font-size: 16px;">{{ $summary['unique_employees'] }}</div>
                                <div class="label" style="font-size: 9px; margin-top: 3px;">{{ __('lang.employees') }}</div>
                            </div>
                        </div>
                    </th>
                </tr>

                <tr class="column_headers">
                    <th class="text-left">{{ __('lang.employee') }}</th>
                    @if (!$branch_id)
                        <th class="text-left">{{ __('lang.branch') }}</th>
                    @endif
                    <th class="text-center">{{ __('lang.date') }}</th>
                    <th class="text-center">{{ __('lang.start_time') }}</th>
                    <th class="text-center">{{ __('lang.end_time') }}</th>
                    <th class="text-center">{{ __('lang.hours') }}</th>
                    <th class="text-center">{{ __('lang.status') }}</th>
                    <th class="text-center">Action By <br><span style="font-size: 10px; font-weight: normal; color: #6b7280;">(Approved / Rejected)</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $index => $item)
                <tr>
                    <td class="font-medium text-gray-900 text-left">{{ $item->employee->name ?? '-' }}</td>
                    @if (!$branch_id)
                        <td class="text-left">{{ $item->employee->branch->name ?? '-' }}</td>
                    @endif
                    <td class="text-center">{{ $item->date }}</td>
                    <td class="text-center">{{ $item->start_time ?? '-' }}</td>
                    <td class="text-center">{{ $item->end_time ?? '-' }}</td>
                    <td class="font-bold text-gray-900 text-center">{{ $item->hours_formatted }}</td>
                    <td class="text-center">
                        <span class="{{ $item->status_badge_class }}">{{ $item->status_label }}</span>
                    </td>
                    <td class="text-center">
                        @if($item->approvedBy)
                            <span class="text-green-600 font-medium">
                                {{ $item->approvedBy->short_name }}
                            </span>
                        @elseif($item->rejectedBy)
                            <span class="text-red-600 font-medium">
                                {{ $item->rejectedBy->short_name }}
                            </span>
                        @else
                            -
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50">
                <tr style="font-weight: 700;">
                    <td colspan="{{ $branch_id ? 4 : 5 }}" class="text-right">{{ __('lang.total') }}</td>
                    <td class="text-center">{{ $summary['total_hours'] }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-4 no-print">
        <x-filament::pagination
            :paginator="$items"
            class="px-3 py-3 sm:px-6" />
    </div>

    @else
    <div style="text-align: center; padding: 80px 0;" class="bg-white rounded-xl border border-gray-200">
        <div style="font-size: 64px; margin-bottom: 20px; opacity: 0.2;">📂</div>
        <p style="font-size: 18px; font-weight: 500; color: #6b7280;">{{ __('lang.no_data') }}</p>
    </div>
    @endif

    {{-- Excel Export Script --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        function exportOvertimeToExcel() {
            var elt = document.getElementById('overtime-report-table');
            var clone = elt.cloneNode(true);

            // Remove the header report row from the excel export to keep it clean
            var headerReport = clone.querySelector('.header_report');
            if (headerReport) headerReport.remove();

            var wb = XLSX.utils.table_to_sheet(clone, {
                raw: true
            });

            var wscols = [];
            for (var i = 0; i < {{ $branch_id ? 7 : 8 }}; i++) {
                wscols.push({
                    wch: 20
                });
            }
            wb['!cols'] = wscols;

            var workbook = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(workbook, wb, "Overtime Report");
            XLSX.writeFile(workbook, "overtime_report.xlsx");
        }
    </script>
</x-filament-panels::page>
