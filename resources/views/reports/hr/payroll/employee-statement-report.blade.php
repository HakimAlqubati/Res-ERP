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

        /* ─── Header Layout & Components ─── */
        .report-header-wrapper {
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .report-header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e2e8f0;
            flex-wrap: wrap;
        }

        .header-col-actions {
            display: flex;
            align-items: center;
            flex: 1;
            min-width: 120px;
        }

        .header-col-title {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            flex: 2;
            min-width: 240px;
        }

        .header-col-logo {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex: 1;
            min-width: 80px;
        }

        .action-btns {
            display: flex;
            flex-direction: column;
            gap: 6px;
            align-items: stretch;
            width: max-content;
        }

        .btn-report-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            height: 30px;
            width: 100%;
            min-width: 118px;
            padding: 0 12px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
            box-sizing: border-box;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-report-action svg {
            width: 14px;
            height: 14px;
            min-width: 14px;
            min-height: 14px;
            flex-shrink: 0;
        }

        .btn-excel {
            background-color: #f0fdf4;
            color: #0b7a5a;
            border: 1.5px solid #0b7a5a;
        }

        .btn-excel:hover {
            background-color: #0b7a5a;
            color: #ffffff;
        }

        .btn-print {
            background-color: #f8fafc;
            color: #334155;
            border: 1.5px solid #cbd5e1;
        }

        .btn-print:hover {
            background-color: #e2e8f0;
            color: #0f172a;
            border-color: #94a3b8;
        }

        .report-main-title {
            font-size: 22px;
            font-weight: 800;
            color: #0d7c66;
            margin: 0 0 6px;
            letter-spacing: -0.3px;
        }

        .report-period-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background-color: #f0fdf4;
            color: #065f46;
            border: 1px solid #a7f3d0;
            padding: 4px 14px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        .report-period-badge svg {
            width: 14px;
            height: 14px;
            color: #0d7c66;
            flex-shrink: 0;
        }

        .company-logo {
            width: 50px;
            height: 50px;
            object-fit: contain;
        }

        /* ─── Employee Profile Banner ─── */
        .employee-profile-banner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            gap: 16px;
            flex-wrap: wrap;
        }

        .employee-profile-main {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .employee-avatar {
            width: 44px;
            height: 44px;
            border-radius: 8px;
            object-fit: cover;
            border: 2px solid #0d7c66;
            box-shadow: 0 2px 6px rgba(13, 124, 102, 0.12);
        }

        .employee-details-box {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .employee-branch-row {
            display: flex;
            align-items: center;
        }

        .employee-name-title {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.2;
        }

        .badge-branch {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #e6f4f1;
            color: #0d7c66;
            font-size: 12px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 6px;
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

        .statement-table .amount-deduction {
            color: #c0392b !important;
            font-weight: 700;
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
            color: #c0392b !important;
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

        :is(.dark, [data-theme="dark"]) .report-header-top {
            border-color: #374151;
        }

        :is(.dark, [data-theme="dark"]) .report-main-title {
            color: #2dd4bf;
        }

        :is(.dark, [data-theme="dark"]) .report-period-badge {
            background-color: rgba(6, 78, 59, 0.4);
            color: #a7f3d0;
            border-color: #059669;
        }

        :is(.dark, [data-theme="dark"]) .report-period-badge svg {
            color: #2dd4bf;
        }

        :is(.dark, [data-theme="dark"]) .employee-profile-banner {
            background: #1e293b;
            border-color: #374151;
        }

        :is(.dark, [data-theme="dark"]) .employee-name-title {
            color: #f8fafc;
        }

        :is(.dark, [data-theme="dark"]) .badge-branch {
            background: #064e3b;
            color: #6ee7b7;
        }

        :is(.dark, [data-theme="dark"]) .btn-excel {
            background-color: #064e3b;
            color: #6ee7b7;
            border: 1.5px solid #059669;
        }

        :is(.dark, [data-theme="dark"]) .btn-excel:hover {
            background-color: #059669;
            color: #ffffff;
            border-color: #059669;
        }

        :is(.dark, [data-theme="dark"]) .btn-print {
            background-color: #1f2937;
            color: #e2e8f0;
            border: 1.5px solid #4b5563;
        }

        :is(.dark, [data-theme="dark"]) .btn-print:hover {
            background-color: #374151;
            color: #ffffff;
            border-color: #6b7280;
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

        :is(.dark, [data-theme="dark"]) .statement-table .amount-deduction {
            color: #f87171 !important;
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

        :is(.dark, [data-theme="dark"]) .statement-table tfoot .total-row-ded td {
            color: #f87171 !important;
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

            .action-btns {
                display: none !important;
            }

            .report-header-top {
                border-bottom: 2px solid #0d7c66 !important;
                padding-bottom: 8px !important;
            }

            .report-main-title {
                color: #0d7c66 !important;
                font-size: 20px !important;
            }

            .report-period-badge {
                background-color: #ffffff !important;
                color: #0d7c66 !important;
                border: 1px solid #0d7c66 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .employee-profile-banner {
                background: #f8fafc !important;
                border: 1px solid #e2e8f0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .employee-name-title {
                color: #0f172a !important;
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

            .statement-table .amount-deduction {
                color: #c0392b !important;
            }

            .statement-table .col-index,
            .statement-table .col-type,
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

            .statement-table tfoot .total-row-ded td {
                color: #c0392b !important;
            }
        }
    </style>

    @if (!empty($reportData) && $reportData['has_data'])
        <div class="report-wrapper">
            <div class="statement-card" id="statement-card">
                {{-- Redesigned Clean Header (Non-redundant) --}}
                <div class="report-header-wrapper">
                    {{-- Row 1: Actions (Left), Main Title & Unified Period (Center), Company Logo (Right) --}}
                    <div class="report-header-top">
                        <div class="header-col-actions">
                            <div class="action-btns">
                                <button type="button" onclick="exportToExcel()" class="btn-report-action btn-excel">
                                    <svg xmlns="http://www.w3.org/2000/svg" style="width: 14px; height: 14px; flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span>{{ __('Export Excel') }}</span>
                                </button>
                                <button type="button" onclick="window.print()" class="btn-report-action btn-print">
                                    <svg xmlns="http://www.w3.org/2000/svg" style="width: 14px; height: 14px; flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                    </svg>
                                    <span>{{ __('Print') }}</span>
                                </button>
                            </div>
                        </div>

                        <div class="header-col-title">
                            <h1 class="report-main-title">{{ __('Financial Statement') }}</h1>
                            <div class="report-period-badge">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span>{{ $reportData['from_date'] }} &nbsp;—&nbsp; {{ $reportData['to_date'] }}</span>
                            </div>
                        </div>

                        <div class="header-col-logo">
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
                            <img src="{{ $logoUrl }}" alt="Company Logo" class="company-logo" onerror="this.style.display='none'">
                        </div>
                    </div>

                    {{-- Row 2: Clean Employee Profile Banner (No redundant dates/titles) --}}
                    <div class="employee-profile-banner">
                        <div class="employee-profile-main">
                            @if ($reportData['avatar_image'])
                                <img src="{{ $reportData['avatar_image'] }}" alt="{{ $reportData['employee_name'] }}" class="employee-avatar">
                            @endif
                            <div class="employee-details-box">
                                <span class="employee-name-title">{{ $reportData['employee_name'] }}</span>
                                @if (!empty($reportData['branch_name']))
                                    <div class="employee-branch-row">
                                        <span class="badge-branch">
                                            <svg xmlns="http://www.w3.org/2000/svg" style="width: 12px; height: 12px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            {{ $reportData['branch_name'] }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Table Structure Matching Attached Design --}}
                <table class="statement-table" id="report-table">
                    <thead>
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 16%;">{{ __('TYPE') }}</th>
                            <th style="width: 8%;">{{ __('OP') }}</th>
                            <th style="width: 16%;">{{ __('AMOUNT') }}</th>
                            <th style="width: 15%;">{{ __('DATE') }}</th>
                            <th style="width: 40%;">{{ __('Transaction Details') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reportData['transactions'] as $tx)
                            <tr class="{{ !empty($tx['is_employer_contribution']) ? 'row-employer-contribution' : '' }}"
                                @if (!empty($tx['is_employer_contribution'])) style="background-color: #e6ffc8;" @endif>
                                <td class="col-index">{{ $tx['index'] }}</td>
                                <td class="col-type">{{ __($tx['type']) }}</td>
                                <td>
                                    @if ($tx['operation'] === '+')
                                        <span class="op-plus">+</span>
                                    @else
                                        <span class="op-minus">-</span>
                                    @endif
                                </td>
                                <td class="col-amount {{ $tx['operation'] === '-' ? 'amount-deduction' : '' }}">{{ $tx['amount'] }}</td>
                                <td class="col-date">{{ $tx['date'] }}</td>
                                <td class="col-desc">{{ $tx['description'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 30px; color: #64748b; font-weight: 600;">
                                    {{ __('No transactions found for this employee within the specified date range.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    <tfoot>
                        <tr class="total-row-add">
                            <td colspan="6">
                                {{ __('Total Additions') }}: {{ $reportData['total_additions'] }}
                            </td>
                        </tr>
                        <tr class="total-row-ded">
                            <td colspan="6">
                                {{ __('Total Deductions') }}: {{ $reportData['total_deductions'] }}
                            </td>
                        </tr>
                        <tr class="total-row-final">
                            <td colspan="6">
                                {{ __('Final Net Result') }}: {{ $reportData['final_result'] }}
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
                    { wch: 20 },
                    { wch: 8 },
                    { wch: 18 },
                    { wch: 14 },
                    { wch: 45 }
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
