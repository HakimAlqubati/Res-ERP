<x-filament-panels::page>
    {{ $this->getTableFiltersForm() }}

    <style>
        /* Base Table Styling - Matching Attendance Report */
        table {
            width: 100%;
            border-collapse: inherit;
            border-spacing: initial;
        }

        .pretty.reports thead th {
            background-color: #f3f4f6;
            color: #374151;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 12px 16px;
            border-bottom: 2px solid #e5e7eb;
        }

        .pretty.reports tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .pretty.reports tbody td {
            padding: 12px 16px;
            border-bottom: 1px solid #e5e7eb;
        }

        /* Buttons Styling */
        .btn-print, .btn-primary, .btn-secondary {
            border-radius: 6px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            cursor: pointer;
        }

        .btn-primary {
            background-color: #22c55e;
            color: white;
            border: 1px solid #16a34a;
        }

        .btn-primary:hover {
            background-color: #16a34a;
        }

        .btn-secondary {
            background-color: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
        }

        .btn-secondary:hover {
            background-color: #e5e7eb;
        }

        /* Status Badges */
        .badge-approved { background-color: #dcfce7; color: #166534; padding: 4px 12px; border-radius: 9999px; font-size: 11px; font-weight: 600; }
        .badge-pending { background-color: #fef9c3; color: #854d0e; padding: 4px 12px; border-radius: 9999px; font-size: 11px; font-weight: 600; }
        .badge-rejected { background-color: #fee2e2; color: #991b1b; padding: 4px 12px; border-radius: 9999px; font-size: 11px; font-weight: 600; }

        /* Summary Grid */
        .overtime-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .overtime-summary-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .overtime-summary-card .value { font-size: 24px; font-weight: 700; color: #111827; }
        .overtime-summary-card .label { font-size: 12px; color: #6b7280; margin-top: 4px; text-transform: uppercase; }

        @media print {
            body * { visibility: hidden; }
            #overtime-report-table, #overtime-report-table * { visibility: visible; }
            #overtime-report-table { position: absolute; top: 0; left: 0; width: 100%; }
            .no-print { display: none !important; }
        }
    </style>

    {{-- Summary Cards --}}
    @if ($summary['total_records'] > 0)
    <div class="overtime-summary-grid no-print">
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

    {{-- Report Table Container --}}
    <div class="overflow-x-auto bg-white rounded-xl border border-gray-200 shadow-sm">
        <table class="w-full text-sm text-left pretty reports" id="overtime-report-table">
            <thead>
                <tr class="header_report">
                    <th colspan="10" style="padding: 16px; background: #f9fafb;">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 20px;">
                            {{-- Left: Actions --}}
                            <div style="display: flex; flex-direction: column; gap: 8px;" class="no-print">
                                <button onclick="exportOvertimeToExcel()" class="btn btn-primary">
                                    &#128200; {{ __('lang.to_excel') }}
                                </button>
                                <button onclick="window.print()" class="btn btn-secondary" style="background-color: #3b82f6; border-color: #2563eb; color: white;">
                                    &#128438; {{ __('lang.print') }}
                                </button>
                            </div>

                            {{-- Center: Employee Context --}}
                            <div style="display: flex; align-items: center; justify-content: center; gap: 12px; flex: 1;">
                                @if ($employee)
                                <img src="{{ $employee->avatar_image }}"
                                    alt="{{ $employee->name }}"
                                    style="width: 80px; height: 80px; border-radius: 12px; object-fit: cover; border: 3px solid #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                                @endif
                                <div style="display: flex; flex-direction: column; text-align: {{ app()->getLocale() == 'ar' ? 'right' : 'left' }};">
                                    <span style="font-size: 16px; font-weight: 700; color: #111827;">
                                        @if($employee)
                                            <a href="{{ \App\Filament\Resources\EmployeeResource::getUrl('view', ['record' => $employee->id]) }}" target="_blank" class="hover:underline">
                                                {{ $employee->name }}
                                            </a>
                                        @elseif($branch_name && $branch_name !== '-')
                                            {{ $branch_name }}
                                        @else
                                            All Branches
                                        @endif
                                    </span>
                                    <span style="font-size: 12px; color: #6b7280; font-weight: 500;">{{ __('lang.overtime_report') }}</span>
                                </div>
                            </div>

                            {{-- Right: Metadata --}}
                            <div style="text-align: right; flex-shrink: 0; line-height: 1.6; color: #374151;">
                                <div style="font-weight: 600;"><span style="color: #6b7280;">{{ __('lang.date') }}:</span> {{ $start_date }} - {{ $end_date }}</div>
                                <div style="font-weight: 600;"><span style="color: #6b7280;">{{ __('lang.branch') }}:</span> {{ $branch_name }}</div>
                            </div>

                            {{-- Far Right: Logo --}}
                            <div style="flex-shrink: 0;">
                                <img src="{{ url('/') . '/' . 'storage/workbench.png' }}" alt="Logo" style="height: 50px;">
                            </div>
                        </div>
                    </th>
                </tr>
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
                    <td>{{ $items->firstItem() + $index }}</td>
                    <td class="font-medium text-gray-900">{{ $item->employee->name ?? '-' }}</td>
                    <td>{{ $item->employee->branch->name ?? '-' }}</td>
                    <td>{{ $item->date }}</td>
                    <td>{{ $item->start_time ?? '-' }}</td>
                    <td>{{ $item->end_time ?? '-' }}</td>
                    <td class="font-bold text-gray-900">{{ $item->hours }}</td>
                    <td>
                        <span class="{{ $item->status_badge_class }}">{{ $item->status_label }}</span>
                    </td>
                    <td>{{ $item->approvedBy->name ?? '-' }}</td>
                    <td class="max-w-xs truncate">{{ $item->notes ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50">
                <tr style="font-weight: 700;">
                    <td colspan="6" class="text-right">{{ __('lang.total') }}</td>
                    <td>{{ $summary['total_hours'] }}</td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-4 no-print">
        <x-filament::pagination
            :paginator="$items"
            class="px-3 py-3 sm:px-6"
        />
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

            var wb = XLSX.utils.table_to_sheet(clone, { raw: true });
            
            var wscols = [];
            for (var i = 0; i < 10; i++) { wscols.push({ wch: 20 }); }
            wb['!cols'] = wscols;

            var workbook = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(workbook, wb, "Overtime Report");
            XLSX.writeFile(workbook, "overtime_report.xlsx");
        }
    </script>
</x-filament-panels::page>