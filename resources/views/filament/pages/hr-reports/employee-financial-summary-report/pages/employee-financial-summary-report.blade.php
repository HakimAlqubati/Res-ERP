<x-filament-panels::page>
    {{ $this->getTableFiltersForm() }}

    <style>
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

            #financial-summary-table,
            #financial-summary-table * {
                visibility: visible;
            }

            #financial-summary-table {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
            }
        }
    </style>

    {{-- Actions --}}
    <div style="display: flex; gap: 8px; margin-bottom: 16px;">
        <button type="button" class="btn-export" style="background-color: #ef4444; border-color: #ef4444;" wire:click="exportPdf">
            &#128196; {{ __('Export PDF') }}
        </button>
    </div>

    <div class="overflow-x-auto">
        {{-- Report Table --}}
        <table class="w-full text-sm text-left pretty reports" id="financial-summary-table">
            <thead class="fixed-header" style="top:64px;">
                <tr class="header_report">
                    <th colspan="{{ empty($branch_id) ? '6' : '5' }}" style="padding: 12px 16px;">
                        @php
                            $branch = \App\Models\Branch::find($branch_id);
                        @endphp
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px;">
                            {{-- Far Left: Company Logo --}}
                            <div style="flex-shrink: 0; text-align: left;">
                                <img class="circle-image" src="{{ asset('/storage/' . setting('company_logo')) }}" alt="Company Logo" style="width: 80px; height: 80px; object-fit: contain;">
                            </div>

                            {{-- Center: Report Name + Branch --}}
                            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; flex: 1;">
                                <span style="font-size: 16px; font-weight: bold; text-transform: uppercase;">{{ __('lang.employee_financial_summary_report') ?? 'Employee Financial Summary' }}</span>
                                <span style="font-size: 14px; font-weight: bold; color: #374151;">{{ $branch?->name ?? __('lang.all_branches') ?? 'All Branches' }}</span>
                            </div>

                            {{-- Far Right: Total + System Logo --}}
                            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 16px; flex-shrink: 0;">
                                <div style="text-align: right; line-height: 1.8;">
                                    <span style="font-weight: 600;">{{ __('lang.total_records') ?? 'Total Employees' }}: {{ $summary['total_records'] }}</span>
                                </div>
                                <div style="text-align: center;">
                                    <img class="circle-image" src="{{ url('/') . '/storage/workbench.png' }}" alt="System Logo" style="width: 80px; height: 80px; object-fit: contain;">
                                </div>
                            </div>
                        </div>
                    </th>
                </tr>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>{{ __('lang.employee') ?? 'Employee' }}</th>
                    @if(empty($branch_id))
                    <th style="text-align:center;">{{ __('lang.branch') ?? 'Branch' }}</th>
                    @endif
                    <th style="text-align:center;">{{ __('lang.incentive_types') ?? 'Incentive Types' }}</th>
                    <th style="text-align:center;">{{ __('lang.allowance_types') ?? 'Allowance Types' }}</th>
                    <th style="text-align:center;">{{ __('lang.deduction_types') ?? 'Deduction Types' }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->employeeName }}</td>
                    @if(empty($branch_id))
                    <td style="text-align:center;">{{ $item->branchName }}</td>
                    @endif
                    <td style="text-align:center;">{{ $item->incentiveTypes }}</td>
                    <td style="text-align:center;">{{ $item->allowanceTypes }}</td>
                    <td style="text-align:center;">{{ $item->deductionTypes }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ empty($branch_id) ? '6' : '5' }}" style="text-align:center; padding: 20px;">{{ __('lang.no_data') ?? 'No Data' }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</x-filament-panels::page>
