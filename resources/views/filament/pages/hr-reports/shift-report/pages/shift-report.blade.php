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

            #shift-report-table,
            #shift-report-table * {
                visibility: visible;
            }

            #shift-report-table {
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

    @if(!empty($branch_id) && !empty($period_ids))
    <div class="overflow-x-auto">
        {{-- Report Table --}}
        <table class="w-full text-sm text-left pretty reports" id="shift-report-table">
            <thead class="fixed-header" style="top:64px;">
                <tr class="header_report">
                    <th colspan="5" style="padding: 12px 16px;">
                        @php
                            $branch = \App\Models\Branch::find($branch_id);
                            $periodNames = \App\Models\WorkPeriod::whereIn('id', $period_ids)->pluck('name')->toArray();
                            $periodTitle = !empty($periodNames) ? implode(', ', $periodNames) : __('lang.shift');
                        @endphp
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px;">
                            {{-- Far Left: Company Logo --}}
                            <div style="flex-shrink: 0; text-align: left;">
                                <img class="circle-image" src="{{ asset('/storage/' . setting('company_logo')) }}" alt="Company Logo" style="width: 80px; height: 80px; object-fit: contain;">
                            </div>

                            {{-- Center: Report Name + Branch + Shift --}}
                            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; flex: 1;">
                                <span style="font-size: 16px; font-weight: bold; text-transform: uppercase;">{{ __('lang.shift_report') }}</span>
                                <span style="font-size: 14px; font-weight: bold; color: #374151;">{{ $branch?->name ?? __('lang.choose_branch') }}</span>
                                <span style="font-size: 13px; color: #6b7280;">
                                    {{ $periodTitle }}
                                </span>
                            </div>

                            {{-- Far Right: Total + System Logo --}}
                            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 16px; flex-shrink: 0;">
                                <div style="text-align: right; line-height: 1.8;">
                                    <span style="font-weight: 600;">{{ __('lang.total_records') }}: {{ $summary['total_records'] }}</span>
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
                    <th>{{ __('lang.employee') }}</th>
                    <th style="text-align:center;">{{ __('lang.shift') }}</th>
                    <th style="text-align:center;">{{ __('lang.start_time') }}</th>
                    <th style="text-align:center;">{{ __('lang.end_time') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item['employee_name'] ?? '-' }}</td>
                    <td style="text-align:center;">{{ $item['period_name'] ?? '-' }}</td>
                    <td style="text-align:center;">{{ $item['period_start_at'] ?? '-' }}</td>
                    <td style="text-align:center;">{{ $item['period_end_at'] ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align:center; padding: 20px;">{{ __('lang.no_data') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @else
    <div class="please_select_message_div" style="text-align: center; margin-top: 40px;">
        <h1 class="please_select_message_text">{{ __('lang.please_select_branch_and_shift') }}</h1>
    </div>
    @endif

    {{-- Excel Export Script --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        function exportShiftReportToExcel() {
            var elt = document.getElementById('shift-report-table');
            var clone = elt.cloneNode(true);
            var wb = XLSX.utils.table_to_sheet(clone, {
                raw: true
            });

            var wscols = [];
            for (var i = 0; i < 5; i++) {
                wscols.push({
                    wch: 22
                });
            }
            wb['!cols'] = wscols;

            var workbook = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(workbook, wb, "Shift Report");
            XLSX.writeFile(workbook, "shift_report.xlsx");
        }
    </script>
</x-filament-panels::page>
