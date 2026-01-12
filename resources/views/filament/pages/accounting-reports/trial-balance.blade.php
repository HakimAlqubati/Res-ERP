<x-filament::page>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        .report-container {
            max-width: 100%;
            margin: 0 auto;
            padding: 2rem;
            background: white;
            border: 2px solid #cbd5e1;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            width: 80%;
        }

        .report-header {
            text-align: center;
            border-radius: 6px;
        }

        .report-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 0 0.5rem 0;
        }

        .report-subtitle {
            font-size: 0.85rem;
            opacity: 0.95;
        }

        .report-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
            overflow: hidden;
            margin-top: 1.5rem;
        }

        .report-table thead {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: white;
        }

        .report-table th {
            padding: 0.85rem 1rem;
            font-size: 0.875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            text-align: left;
            border-bottom: 2px solid #1e40af;
        }

        .report-table th:last-child,
        .report-table td:last-child {
            text-align: right;
        }

        .table-row {
            transition: all 0.2s ease;
            border-bottom: 1px solid #e5e7eb;
        }

        .table-row:nth-child(even) {
            background-color: #f9fafb;
        }

        .table-row:hover {
            background-color: #eff6ff;
            transform: translateX(2px);
        }

        .table-row td {
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            color: #1f2937;
        }

        .account-code {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: #3b82f6;
        }

        .debit-amount {
            color: #059669;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }

        .credit-amount {
            color: #dc2626;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }

        .total-row {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-top: 3px solid #cbd5e1;
            border-bottom: 3px solid #cbd5e1;
            font-weight: 700;
        }

        .total-row td {
            padding: 1rem;
            font-size: 1rem;
            color: #0f172a;
        }

        .balance-status {
            margin-top: 1.5rem;
            padding: 1rem 1.5rem;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .balanced {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border: 2px solid #059669;
        }

        .unbalanced {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border: 2px solid #dc2626;
        }

        .balance-label {
            font-size: 1.1rem;
            font-weight: 700;
        }

        .balance-amount {
            font-size: 1.2rem;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            background: white;
            border-radius: 6px;
            border: 2px dashed #d1d5db;
        }

        .empty-text {
            font-size: 1rem;
            color: #6b7280;
            font-weight: 500;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: white !important;
            }

            .table-row:hover {
                background-color: transparent;
            }
        }

        @media (max-width: 768px) {
            .report-container {
                max-width: 100%;
                padding: 1rem;
                width: 100%;
            }

            .report-title {
                font-size: 1.25rem;
            }

            .report-table {
                font-size: 0.75rem;
            }

            .report-table th,
            .report-table td {
                padding: 0.5rem;
            }
        }

        [x-cloak] {
            display: none !important;
        }

        /* Dark Mode Styles */
        .dark .report-container {
            background: #1e293b;
            border-color: #334155;
        }

        .dark .report-table {
            background: #1e293b;
        }

        .dark .report-table thead {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
        }

        .dark .table-row {
            border-bottom-color: #334155;
        }

        .dark .table-row:nth-child(even) {
            background-color: #334155;
        }

        .dark .table-row:hover {
            background-color: #3b4f6b;
        }

        .dark .table-row td {
            color: #f1f5f9;
        }

        .dark .total-row {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-color: #475569;
        }

        .dark .total-row td {
            color: #f1f5f9;
        }

        .dark .balanced {
            background: linear-gradient(135deg, #064e3b 0%, #065f46 100%);
            border-color: #10b981;
        }

        .dark .unbalanced {
            background: linear-gradient(135deg, #450a0a 0%, #7f1d1d 100%);
            border-color: #f87171;
        }

        .dark .empty-state {
            background: #1e293b;
            border-color: #475569;
        }

        .dark .empty-text {
            color: #94a3b8;
        }
    </style>
    
    {{-- Filters Form --}}
    <div class="no-print mb-6 bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        {{ $this->getTableFiltersForm() }}
    </div>

    <div class="report-container">
        @if (isset($report) && !empty($report['accounts']))
        {{-- Header --}}
        <div class="report-header" style="display: flex; justify-content: space-between;">
            {{-- Company Logo (Left) --}}
            <div style="flex: 0 0 80px;">
                <img src="{{ asset('/storage/' . setting('company_logo')) }}" alt="Company Logo"
                    style="height: 60px; width: auto; object-fit: contain; border-radius: 0%;">
            </div>

            {{-- Title (Center) --}}
            <div style="flex: 1; text-align: center;">
                <h1 class="report-title">{{ __('lang.trial_balance_report') }}</h1>
                <p class="report-subtitle">
                    {{ __('lang.from') }} {{ \Carbon\Carbon::parse($startDate)->format('d F, Y') }}
                    {{ __('lang.to') }} {{ \Carbon\Carbon::parse($endDate)->format('d F, Y') }}
                </p>
                @if(isset($accountType) && $accountType)
                <p class="report-subtitle" style="font-weight: 600; margin-top: 0.25rem;">
                    {{ __('lang.account_type') }}: {{ __('lang.' . $accountType . 's') }}
                </p>
                @endif
            </div>

            {{-- System Logo (Right) --}}
            <div style="flex: 0 0 80px; text-align: right;">
                <img src="{{ url('/') . '/storage/workbench.png' }}" alt="System Logo"
                    style="height: 40px; width: auto; object-fit: contain;">
            </div>
        </div>

        {{-- Report Table --}}
        <table class="report-table">
            <thead>
                <tr>
                    <th>{{ __('lang.account_code') }}</th>
                    <th>{{ __('lang.account_name') }}</th>
                    <th>{{ __('lang.debit') }}</th>
                    <th>{{ __('lang.credit') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report['accounts'] as $account)
                <tr class="table-row">
                    <td class="account-code">{{ $account['account_code'] }}</td>
                    <td>{{ $account['account_name'] }}</td>
                    <td class="debit-amount">{{ $account['debit_formatted'] }}</td>
                    <td class="credit-amount">{{ $account['credit_formatted'] }}</td>
                </tr>
                @endforeach

                {{-- Total Row --}}
                <tr class="total-row">
                    <td colspan="2"><strong>{{ __('lang.total') }}</strong></td>
                    <td class="debit-amount">{{ $report['totals']['total_debit_formatted'] }}</td>
                    <td class="credit-amount">{{ $report['totals']['total_credit_formatted'] }}</td>
                </tr>
            </tbody>
        </table>

        {{-- Balance Status --}}
        <div class="balance-status {{ $report['totals']['is_balanced'] ? 'balanced' : 'unbalanced' }}">
            <div class="balance-label">
                @if($report['totals']['is_balanced'])
                ✅ {{ __('lang.report_is_balanced') }}
                @else
                ❌ {{ __('lang.report_out_of_balance') }}
                @endif
            </div>
            <div class="balance-amount" style="color: {{ $report['totals']['is_balanced'] ? '#059669' : '#dc2626' }};">
                {{ __('lang.difference') }}: {{ $report['totals']['difference_formatted'] }}
            </div>
        </div>

        {{-- Generated On --}}
        <div style="margin-top: 2.5rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb;">
            <div style="display: flex; align-items: center;">
                {{-- Empty left space --}}
                <div style="flex: 1;"></div>
                {{-- Center: Generated on --}}
                <div style="flex: 1; text-align: center;">
                    <p style="font-size: 0.7rem; color: #6b7280; margin: 0; white-space: nowrap;">
                        {{ __('lang.generated_on') }} {{ \Carbon\Carbon::now()->format('d F, Y - h:i A') }}
                    </p>
                    <p style="font-size: 0.7rem; color: #6b7280; margin: 0.25rem 0 0 0;">
                        {{ __('lang.total_accounts') }}: {{ $report['summary']['account_count'] }}
                    </p>
                </div>
                {{-- Right: Powered by AWS --}}
                <div style="flex: 1; display: flex; justify-content: flex-end; align-items: center; gap: 0.5rem;">
                    <span style="font-size: 0.7rem; color: #6b7280;">Powered by</span>
                    <img src="https://upload.wikimedia.org/wikipedia/commons/9/93/Amazon_Web_Services_Logo.svg"
                        alt="AWS Logo"
                        style="height: 18px; width: auto;">
                </div>
            </div>
        </div>
        @else
        <div class="empty-state">
            <p class="empty-text">{{ __('lang.please_select_filters_to_generate_report') }}</p>
        </div>
        @endif
    </div>
</x-filament::page>