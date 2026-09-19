<x-filament-panels::page>
    {{ $this->getTableFiltersForm() }}

    <style>
        .report-wrapper {
            width: 100%;
            margin-top: 16px;
        }

        /* ─── Light Mode Styles (Default) ─── */
        .statement-card {
            border: 2px solid #0d7c66;
            border-radius: 8px;
            padding: 24px;
            background: #ffffff;
            box-shadow: 0 4px 14px rgba(13, 124, 102, 0.08);
            position: relative;
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }

        .report-header-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 14px;
            border-bottom: 1px dashed #e2e8f0;
            gap: 16px;
            flex-wrap: wrap;
        }

        .action-btns {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .btn-report-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-excel {
            background-color: #f0fdf4;
            color: #0b7a5a;
            border: 1px solid #0b7a5a;
        }

        .btn-excel:hover {
            background-color: #0b7a5a;
            color: #ffffff;
        }

        .btn-print {
            background-color: #f8fafc;
            color: #334155;
            border: 1px solid #cbd5e1;
        }

        .btn-print:hover {
            background-color: #e2e8f0;
            color: #0f172a;
        }

        .report-title {
            text-align: center;
            font-size: 26px;
            font-weight: 700;
            color: #0d7c66;
            margin: 0 0 6px;
            letter-spacing: -0.5px;
        }

        .report-subtitle {
            text-align: center;
            font-size: 16px;
            font-weight: 500;
            color: #555555;
            margin: 0 0 20px;
        }

        .employee-meta-name {
            font-weight: 700;
            color: #1e293b;
            font-size: 15px;
        }

        .employee-meta-period {
            font-size: 12px;
            color: #64748b;
        }

        .employee-meta-period strong {
            color: #1e293b;
        }

        .badge-subtle {
            display: inline-block;
            background: #e6f4f1;
            color: #0d7c66;
            font-size: 12px;
            font-weight: 600;
            border-radius: 4px;
            padding: 2px 8px;
        }

        .statement-table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
            margin-top: 10px;
            font-size: 14px;
        }

        .statement-table th,
        .statement-table td {
            border: 1px solid #e5e7eb;
            padding: 10px 12px;
            text-align: center;
            vertical-align: middle;
        }

        .statement-table th {
            background-color: #0d7c66;
            color: #ffffff;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 13px;
        }

        /* Explicit light mode row and column colors */
        .statement-table td {
            color: #1e293b;
        }

        .statement-table tbody tr:nth-child(odd) {
            background-color: #ffffff;
        }

        .statement-table tbody tr:nth-child(even) {
            background-color: #f5f9f8;
        }

        .statement-table tbody tr:hover {
            background-color: #eef7f5;
        }

        .statement-table .col-index {
            font-weight: 600;
            color: #1e293b;
        }

        .statement-table .col-type {
            font-weight: 500;
            color: #1e293b;
        }

        .statement-table .col-sub-type {
            color: #475569;
        }

        .statement-table .col-amount {
            font-weight: 600;
            color: #1e293b;
            text-align: center;
        }

        .statement-table .col-date {
            color: #334155;
        }

        .statement-table .col-desc {
            color: #1e293b;
            text-align: left;
            padding-left: 14px;
        }

        .statement-table .op-plus {
            color: #0d7c66;
            font-weight: 700;
            font-size: 16px;
        }

        .statement-table .op-minus {
            color: #c0392b;
            font-weight: 700;
            font-size: 16px;
        }

        .statement-table tfoot td {
            font-weight: 700;
            text-align: right;
            color: #0d7c66;
            font-size: 16px;
            padding: 8px 16px;
            background-color: #ffffff;
        }

        .statement-table tfoot .total-row-add td {
            border-bottom: none;
            border-top: 1px solid #e5e7eb;
        }

        .statement-table tfoot .total-row-ded td {
            border-bottom: none;
            border-top: none;
        }

        .statement-table tfoot .total-row-final td {
            border-top: none;
            font-size: 17px;
        }

        /* ─── Dark Mode Support ─── */
        :is(.dark, [data-theme="dark"]) .statement-card {
            background: #111827;
            border-color: #0d7c66;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.45);
        }

        :is(.dark, [data-theme="dark"]) .report-header-toolbar {
            border-color: #374151;
        }

        :is(.dark, [data-theme="dark"]) .report-title {
            color: #2dd4bf;
        }

        :is(.dark, [data-theme="dark"]) .report-subtitle {
            color: #94a3b8;
        }

        :is(.dark, [data-theme="dark"]) .employee-meta-name {
            color: #f8fafc;
        }

        :is(.dark, [data-theme="dark"]) .employee-meta-period {
            color: #94a3b8;
        }

        :is(.dark, [data-theme="dark"]) .employee-meta-period strong {
            color: #f1f5f9;
        }

        :is(.dark, [data-theme="dark"]) .badge-subtle {
            background: #064e3b;
            color: #a7f3d0;
        }

        :is(.dark, [data-theme="dark"]) .btn-excel {
            background-color: #064e3b;
            color: #6ee7b7;
            border-color: #059669;
        }

        :is(.dark, [data-theme="dark"]) .btn-excel:hover {
            background-color: #059669;
            color: #ffffff;
        }

        :is(.dark, [data-theme="dark"]) .btn-print {
            background-color: #1f2937;
            color: #e2e8f0;
            border-color: #4b5563;
        }

        :is(.dark, [data-theme="dark"]) .btn-print:hover {
            background-color: #374151;
            color: #ffffff;
        }

        :is(.dark, [data-theme="dark"]) .statement-table {
            background: #111827;
        }

        :is(.dark, [data-theme="dark"]) .statement-table th {
            background-color: #0d7c66;
            color: #ffffff;
            border-color: #0d7c66;
        }

        :is(.dark, [data-theme="dark"]) .statement-table td {
            border-color: #374151;
            color: #f1f5f9 !important;
        }

        :is(.dark, [data-theme="dark"]) .statement-table tbody tr:nth-child(odd) {
            background-color: #111827;
        }

        :is(.dark, [data-theme="dark"]) .statement-table tbody tr:nth-child(even) {
            background-color: #1f2937;
        }

        :is(.dark, [data-theme="dark"]) .statement-table tbody tr:hover {
            background-color: #2d3748;
        }

        :is(.dark, [data-theme="dark"]) .statement-table .col-index,
        :is(.dark, [data-theme="dark"]) .statement-table .col-type,
        :is(.dark, [data-theme="dark"]) .statement-table .col-amount {
            color: #f8fafc !important;
        }

        :is(.dark, [data-theme="dark"]) .statement-table .col-sub-type {
            color: #94a3b8 !important;
        }

        :is(.dark, [data-theme="dark"]) .statement-table .col-date {
            color: #cbd5e1 !important;
        }

        :is(.dark, [data-theme="dark"]) .statement-table .col-desc {
            color: #f1f5f9 !important;
        }

        :is(.dark, [data-theme="dark"]) .statement-table .op-plus {
            color: #34d399;
        }

        :is(.dark, [data-theme="dark"]) .statement-table .op-minus {
            color: #f87171;
        }

        :is(.dark, [data-theme="dark"]) .statement-table .row-employer-contribution {
            background-color: #133827 !important;
        }

        :is(.dark, [data-theme="dark"]) .statement-table tfoot td {
            background-color: #111827;
            color: #2dd4bf !important;
        }

        :is(.dark, [data-theme="dark"]) .statement-table tfoot .total-row-add td {
            border-top: 1px solid #374151;
        }

        /* ─── Empty State ─── */
        .empty-state-card {
            text-align: center;
            padding: 48px 24px;
            background: #ffffff;
            border: 2px dashed #0d7c66;
            border-radius: 8px;
            margin-top: 16px;
            transition: all 0.2s ease;
        }

        .empty-state-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background-color: #e6f4f1;
            color: #0d7c66;
            margin-bottom: 16px;
        }

        .empty-state-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .empty-state-desc {
            color: #64748b;
            max-width: 500px;
            margin: 0 auto;
            font-size: 14px;
        }

        :is(.dark, [data-theme="dark"]) .empty-state-card {
            background: #111827;
            border-color: #0d7c66;
        }

        :is(.dark, [data-theme="dark"]) .empty-state-icon {
            background-color: #064e3b;
            color: #2dd4bf;
        }

        :is(.dark, [data-theme="dark"]) .empty-state-title {
            color: #f8fafc;
        }

        :is(.dark, [data-theme="dark"]) .empty-state-desc {
            color: #94a3b8;
        }

        /* ─── Print Specific Styling ─── */
        @media print {
            body * {
                visibility: hidden !important;
            }

            .statement-card,
            .statement-card * {
                visibility: visible !important;
            }

            .statement-card {
                position: absolute;
                top: 0;
                left: 0;
                width: 100% !important;
                background: #ffffff !important;
                border: 2px solid #0d7c66 !important;
                box-shadow: none !important;
                padding: 10px !important;
            }

            .report-header-toolbar {
                display: none !important;
            }

            .report-title {
                color: #0d7c66 !important;
            }

            .report-subtitle {
                color: #555555 !important;
            }

            .statement-table {
                width: 100% !important;
                background: #ffffff !important;
                border-collapse: collapse !important;
            }

            .statement-table th {
                background-color: #0d7c66 !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .statement-table td {
                color: #111827 !important;
                border: 1px solid #e5e7eb !important;
            }

            .statement-table tbody tr:nth-child(odd) {
                background-color: #ffffff !important;
            }

            .statement-table tbody tr:nth-child(even) {
                background-color: #f5f9f8 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .statement-table .op-plus {
                color: #0d7c66 !important;
            }

            .statement-table .op-minus {
                color: #c0392b !important;
            }

            .statement-table .col-index,
            .statement-table .col-type,
            .statement-table .col-sub-type,
            .statement-table .col-amount,
            .statement-table .col-date,
            .statement-table .col-desc {
                color: #111827 !important;
            }

            .statement-table tfoot td {
                background-color: #ffffff !important;
                color: #0d7c66 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>

    @if (!empty($reportData) && $reportData['has_data'])
        <div class="report-wrapper">
            <div class="statement-card" id="statement-card">
                {{-- Action Bar --}}
                <div class="report-header-toolbar">
                    <div class="action-btns">
                        <button type="button" onclick="exportToExcel()" class="btn-report-action btn-excel">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            {{ __('Export Excel') }}
                        </button>
                        <button type="button" onclick="window.print()" class="btn-report-action btn-print">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            {{ __('Print') }}
                        </button>
                    </div>

                    {{-- Employee & Period Badges --}}
                    <div style="display: flex; align-items: center; gap: 14px;">
                        @if ($reportData['avatar_image'])
                            <img src="{{ $reportData['avatar_image'] }}" alt="{{ $reportData['employee_name'] }}"
                                style="width: 44px; height: 44px; border-radius: 8px; object-fit: cover; border: 2px solid #0d7c66;">
                        @endif
                        <div style="text-align: right; line-height: 1.4;">
                            <span class="employee-meta-name">{{ $reportData['employee_name'] }}</span>
                            @if (!empty($reportData['branch_name']))
                                <span class="badge-subtle" style="margin-inline-start: 6px;">{{ $reportData['branch_name'] }}</span>
                            @endif
                            <div class="employee-meta-period">
                                {{ __('From') }}: <strong>{{ $reportData['from_date'] }}</strong> &nbsp;|&nbsp; {{ __('To') }}: <strong>{{ $reportData['to_date'] }}</strong>
                            </div>
                        </div>
                        @php
                            $companyLogo = setting('company_logo');
                            if ($companyLogo) {
                                $logoUrl = str_starts_with($companyLogo, 'http')
                                    ? $companyLogo
                                    : (str_starts_with($companyLogo, 'storage/') || str_starts_with($companyLogo, '/storage/')
                                        ? asset($companyLogo)
                                        : asset('/storage/' . $companyLogo));
                            } else {
                                $logoUrl = asset('workbench.png');
                            }
                        @endphp
                        <img src="{{ $logoUrl }}" alt="Company Logo" onerror="this.style.display='none'"
                            style="width: 50px; height: 50px; object-fit: contain;">
                    </div>
                </div>

                {{-- Header Titles Matching Attached Design --}}
                <h1 class="report-title">{{ __('Payroll Transactions') }}</h1>
                <h2 class="report-subtitle">{{ $reportData['employee_name'] }} — {{ $reportData['period_label'] }}</h2>

                {{-- Table Structure Matching Attached Design --}}
                <table class="statement-table" id="report-table">
                    <thead>
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 15%;">{{ __('TYPE') }}</th>
                            <th style="width: 16%;">{{ __('SUB TYPE') }}</th>
                            <th style="width: 8%;">{{ __('OP') }}</th>
                            <th style="width: 15%;">{{ __('AMOUNT') }}</th>
                            <th style="width: 14%;">{{ __('DATE') }}</th>
                            <th style="width: 27%;">{{ __('DESCRIPTION') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reportData['transactions'] as $tx)
                            <tr class="{{ !empty($tx['is_employer_contribution']) ? 'row-employer-contribution' : '' }}"
                                @if (!empty($tx['is_employer_contribution'])) style="background-color: #e6ffc8;" @endif>
                                <td class="col-index">{{ $tx['index'] }}</td>
                                <td class="col-type">{{ __($tx['type']) }}</td>
                                <td class="col-sub-type">{{ !empty($tx['sub_type']) ? __($tx['sub_type']) : '' }}</td>
                                <td>
                                    @if ($tx['operation'] === '+')
                                        <span class="op-plus">+</span>
                                    @else
                                        <span class="op-minus">-</span>
                                    @endif
                                </td>
                                <td class="col-amount">{{ $tx['amount'] }}</td>
                                <td class="col-date">{{ $tx['date'] }}</td>
                                <td class="col-desc">{{ $tx['description'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 30px; color: #64748b; font-weight: 600;">
                                    {{ __('No transactions found for this employee within the specified date range.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    <tfoot>
                        <tr class="total-row-add">
                            <td colspan="7">
                                {{ __('Total Additions') }}: {{ $reportData['total_additions'] }}
                            </td>
                        </tr>
                        <tr class="total-row-ded">
                            <td colspan="7">
                                {{ __('Total Deductions') }}: {{ $reportData['total_deductions'] }}
                            </td>
                        </tr>
                        <tr class="total-row-final">
                            <td colspan="7">
                                {{ __('Final Result') }}: {{ $reportData['final_result'] }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Excel Export Script using SheetJS --}}
        <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
        <script>
            function exportToExcel() {
                var elt = document.getElementById('report-table');
                if (!elt) return;

                var clone = elt.cloneNode(true);
                clone.querySelectorAll('button').forEach(btn => btn.remove());

                var wb = XLSX.utils.table_to_sheet(clone, { raw: true });
                wb['!cols'] = [
                    { wch: 6 },
                    { wch: 18 },
                    { wch: 22 },
                    { wch: 8 },
                    { wch: 18 },
                    { wch: 14 },
                    { wch: 38 }
                ];

                var workbook = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(workbook, wb, "Payroll Transactions");

                var employeeName = "{{ preg_replace('/[^A-Za-z0-9_\\-]/', '_', $reportData['employee_name'] ?? 'Employee') }}";
                var fromDate = "{{ $reportData['from_date'] ?? '' }}";
                var toDate = "{{ $reportData['to_date'] ?? '' }}";
                XLSX.writeFile(workbook, "Payroll_Transactions_" + employeeName + "_" + fromDate + "_to_" + toDate + ".xlsx");
            }
        </script>

    @else
        {{-- Friendly Empty State Prompt --}}
        <div class="empty-state-card">
            <div class="empty-state-icon">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <h3 class="empty-state-title">
                {{ __('Select Employee & Date Range') }}
            </h3>
            <p class="empty-state-desc">
                {{ __('Please select an employee and choose the start and end dates from the filters above to generate the payroll transactions statement.') }}
            </p>
        </div>
    @endif
</x-filament-panels::page>
