<x-filament-panels::page>
    {{-- Filters --}}
    {{ $this->getTableFiltersForm() }}

    @if (!empty($branch_id) && $report)
    <div class="flex justify-end mb-4 mt-4 no-print">
        <x-filament::button wire:click="exportPdf" size="sm" color="success" icon="heroicon-o-document-arrow-down">
            {{ __('Export PDF') }}
        </x-filament::button>
    </div>
    <div class="overflow-x-auto">
        {{-- ============ CURRENT STAFF ============ --}}
        <table class="w-full text-sm text-left pretty reports" style="margin-bottom: 2rem;">
            <thead class="fixed-header" style="top:64px;">
                <tr class="header_report">
                    <th colspan="8" class="no_border_right_left" style="text-align:center; font-size:14px;">
                        {{ __('lang.current_staff') }} &mdash; {{ $report['period'] }}
                    </th>
                </tr>
                <tr>
                    <th rowspan="2" style="width:40px;">{{ __('No.') }}</th>
                    <th rowspan="2">{{ __('lang.employee_name') }}</th>
                    <th rowspan="2" style="text-align:center;">{{ __('Present Days') }}</th>
                    <th colspan="2" style="text-align:center;">{{ __('lang.overtime') }}</th>
                    <th colspan="2" style="text-align:center;">{{ __('lang.deductions') }}</th>
                    <th rowspan="2">{{ __('lang.note') }}</th>
                </tr>
                <tr>
                    <th style="text-align:center;">{{ __('lang.days') }}</th>
                    <th style="text-align:center;">{{ __('lang.hours') }}</th>
                    <th style="text-align:center;">{{ __('lang.days') }}</th>
                    <th style="text-align:center;">{{ __('lang.hours') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($report['current_staff'] as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td style="text-align:center;">{{ $row['attendance']['present_days'] ?? '0' }}</td>
                    <td style="text-align:center;">{{ $row['overtime']['days'] ?: '0' }}</td>
                    <td style="text-align:center;">{{ $row['overtime']['hours'] ?: '0' }}</td>
                    <td style="text-align:center;">{{ $row['deductions']['days'] ?: '0' }}</td>
                    <td style="text-align:center;">{{ $row['deductions']['hours'] ?: '0' }}</td>
                    <td>{{ $row['note'] }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align:center;">{{ __('lang.no_data') }}</td>
                </tr>
                @endforelse
            </tbody>
            @if(count($report['current_staff']) > 0)
            <tfoot style="font-weight: bold; background-color: #f3f4f6;">
                <tr>
                    <td colspan="2" style="text-align:right;">{{ __('Total') }}</td>
                    <td style="text-align:center;">{{ $report['totals']['current_staff']['present_days'] }}</td>
                    <td style="text-align:center;">{{ $report['totals']['current_staff']['overtime_days'] }}</td>
                    <td style="text-align:center;">{{ $report['totals']['current_staff']['overtime_hours'] }}</td>
                    <td style="text-align:center;">{{ $report['totals']['current_staff']['deduction_days'] }}</td>
                    <td style="text-align:center;">{{ $report['totals']['current_staff']['deduction_hours'] }}</td>
                    <td></td>
                </tr>
            </tfoot>
            @endif
        </table>

        {{-- ============ NEW STAFF ============ --}}
        <table class="w-full text-sm text-left pretty reports" style="margin-bottom: 2rem;">
            <thead>
                <tr class="header_report">
                    <th colspan="9" class="no_border_right_left" style="text-align:center; font-size:14px;">
                        {{ __('lang.new_staff') }} &mdash; {{ $report['period'] }}
                    </th>
                </tr>
                <tr>
                    <th rowspan="2" style="width:40px;">{{ __('No.') }}</th>
                    <th rowspan="2">{{ __('lang.employee_name') }}</th>
                    <th rowspan="2" style="text-align:center;">{{ __('Present Days') }}</th>
                    <th colspan="2" style="text-align:center;">{{ __('lang.overtime') }}</th>
                    <th colspan="2" style="text-align:center;">{{ __('lang.deductions') }}</th>
                    <th rowspan="2">{{ __('lang.salary') }}</th>
                    <th rowspan="2">{{ __('lang.note') }}</th>
                </tr>
                <tr>
                    <th style="text-align:center;">{{ __('lang.days') }}</th>
                    <th style="text-align:center;">{{ __('lang.hours') }}</th>
                    <th style="text-align:center;">{{ __('lang.days') }}</th>
                    <th style="text-align:center;">{{ __('lang.hours') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($report['new_staff'] as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td style="text-align:center;">{{ $row['attendance']['present_days'] ?? '0' }}</td>
                    <td style="text-align:center;">{{ $row['overtime']['days'] ?: '0' }}</td>
                    <td style="text-align:center;">{{ $row['overtime']['hours'] ?: '0' }}</td>
                    <td style="text-align:center;">{{ $row['deductions']['days'] ?: '0' }}</td>
                    <td style="text-align:center;">{{ $row['deductions']['hours'] ?: '0' }}</td>
                    <td style="text-align:center;">{{ $row['salary'] ?? '0' }}</td>
                    <td>{{ $row['note'] }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align:center;">-</td>
                </tr>
                @endforelse
            </tbody>
            @if(count($report['new_staff']) > 0)
            <tfoot style="font-weight: bold; background-color: #f3f4f6;">
                <tr>
                    <td colspan="2" style="text-align:right;">{{ __('Total') }}</td>
                    <td style="text-align:center;">{{ $report['totals']['new_staff']['present_days'] }}</td>
                    <td style="text-align:center;">{{ $report['totals']['new_staff']['overtime_days'] }}</td>
                    <td style="text-align:center;">{{ $report['totals']['new_staff']['overtime_hours'] }}</td>
                    <td style="text-align:center;">{{ $report['totals']['new_staff']['deduction_days'] }}</td>
                    <td style="text-align:center;">{{ $report['totals']['new_staff']['deduction_hours'] }}</td>
                    <td style="text-align:center;">{{ $report['totals']['new_staff']['salary'] }}</td>
                    <td></td>
                </tr>
            </tfoot>
            @endif
        </table>

        {{-- ============ TERMINATED STAFF ============ --}}
        <table class="w-full text-sm text-left pretty reports">
            <thead>
                <tr class="header_report">
                    <th colspan="9" class="no_border_right_left" style="text-align:center; font-size:14px;">
                        {{ __('lang.terminated_staff') }} &mdash; {{ $report['period'] }}
                    </th>
                </tr>
                <tr>
                    <th rowspan="2" style="width:40px;">{{ __('No.') }}</th>
                    <th rowspan="2">{{ __('lang.employee_name') }}</th>
                    <th rowspan="2" style="text-align:center;">{{ __('Present Days') }}</th>
                    <th colspan="2" style="text-align:center;">{{ __('lang.overtime') }}</th>
                    <th colspan="2" style="text-align:center;">{{ __('lang.deductions') }}</th>
                    <th rowspan="2">{{ __('lang.termination_date') }}</th>
                    <th rowspan="2">{{ __('lang.note') }}</th>
                </tr>
                <tr>
                    <th style="text-align:center;">{{ __('lang.days') }}</th>
                    <th style="text-align:center;">{{ __('lang.hours') }}</th>
                    <th style="text-align:center;">{{ __('lang.days') }}</th>
                    <th style="text-align:center;">{{ __('lang.hours') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($report['terminated_staff'] as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td style="text-align:center;">{{ $row['attendance']['present_days'] ?? '0' }}</td>
                    <td style="text-align:center;">{{ $row['overtime']['days'] ?: '0' }}</td>
                    <td style="text-align:center;">{{ $row['overtime']['hours'] ?: '0' }}</td>
                    <td style="text-align:center;">{{ $row['deductions']['days'] ?: '0' }}</td>
                    <td style="text-align:center;">{{ $row['deductions']['hours'] ?: '0' }}</td>
                    <td style="text-align:center;">{{ $row['termination_date'] ?? '0' }}</td>
                    <td>{{ $row['note'] }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align:center;">-</td>
                </tr>
                @endforelse
            </tbody>
            @if(count($report['terminated_staff']) > 0)
            <tfoot style="font-weight: bold; background-color: #f3f4f6;">
                <tr>
                    <td colspan="2" style="text-align:right;">{{ __('Total') }}</td>
                    <td style="text-align:center;">{{ $report['totals']['terminated_staff']['present_days'] }}</td>
                    <td style="text-align:center;">{{ $report['totals']['terminated_staff']['overtime_days'] }}</td>
                    <td style="text-align:center;">{{ $report['totals']['terminated_staff']['overtime_hours'] }}</td>
                    <td style="text-align:center;">{{ $report['totals']['terminated_staff']['deduction_days'] }}</td>
                    <td style="text-align:center;">{{ $report['totals']['terminated_staff']['deduction_hours'] }}</td>
                    <td></td>
                    <td></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
    @else
    <div class="please_select_message_div" style="text-align: center;">
        <h1 class="please_select_message_text">{{ __('Please select a Branch and Month') }}</h1>
    </div>
    @endif
</x-filament-panels::page>