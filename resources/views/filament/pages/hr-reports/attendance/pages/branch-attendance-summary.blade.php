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
                    <th colspan="7" class="no_border_right_left" style="text-align:center; font-size:14px;">
                        {{ __('lang.current_staff') }} &mdash; {{ $report['period'] }}
                    </th>
                </tr>
                <tr>
                    <th rowspan="2" style="width:40px;">{{ __('No.') }}</th>
                    <th rowspan="2">{{ __('lang.employee_name') }}</th>
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
                    <td style="text-align:center;">{{ $row['overtime']['days'] ?: '-' }}</td>
                    <td style="text-align:center;">{{ $row['overtime']['hours'] ?: '-' }}</td>
                    <td style="text-align:center;">{{ $row['deductions']['days'] ?: '-' }}</td>
                    <td style="text-align:center;">{{ $row['deductions']['hours'] ?: '-' }}</td>
                    <td>{{ $row['note'] }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center;">{{ __('lang.no_data') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        {{-- ============ NEW STAFF ============ --}}
        <table class="w-full text-sm text-left pretty reports" style="margin-bottom: 2rem;">
            <thead>
                <tr class="header_report">
                    <th colspan="8" class="no_border_right_left" style="text-align:center; font-size:14px;">
                        {{ __('lang.new_staff') }} &mdash; {{ $report['period'] }}
                    </th>
                </tr>
                <tr>
                    <th rowspan="2" style="width:40px;">{{ __('No.') }}</th>
                    <th rowspan="2">{{ __('lang.employee_name') }}</th>
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
                    <td style="text-align:center;">{{ $row['overtime']['days'] ?: '-' }}</td>
                    <td style="text-align:center;">{{ $row['overtime']['hours'] ?: '-' }}</td>
                    <td style="text-align:center;">{{ $row['deductions']['days'] ?: '-' }}</td>
                    <td style="text-align:center;">{{ $row['deductions']['hours'] ?: '-' }}</td>
                    <td style="text-align:center;">{{ $row['salary'] ?? '-' }}</td>
                    <td>{{ $row['note'] }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align:center;">-</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        {{-- ============ TERMINATED STAFF ============ --}}
        <table class="w-full text-sm text-left pretty reports">
            <thead>
                <tr class="header_report">
                    <th colspan="8" class="no_border_right_left" style="text-align:center; font-size:14px;">
                        {{ __('lang.terminated_staff') }} &mdash; {{ $report['period'] }}
                    </th>
                </tr>
                <tr>
                    <th rowspan="2" style="width:40px;">{{ __('No.') }}</th>
                    <th rowspan="2">{{ __('lang.employee_name') }}</th>
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
                    <td style="text-align:center;">{{ $row['overtime']['days'] ?: '-' }}</td>
                    <td style="text-align:center;">{{ $row['overtime']['hours'] ?: '-' }}</td>
                    <td style="text-align:center;">{{ $row['deductions']['days'] ?: '-' }}</td>
                    <td style="text-align:center;">{{ $row['deductions']['hours'] ?: '-' }}</td>
                    <td style="text-align:center;">{{ $row['termination_date'] ?? '-' }}</td>
                    <td>{{ $row['note'] }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align:center;">-</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @else
    <div class="please_select_message_div" style="text-align: center;">
        <h1 class="please_select_message_text">{{ __('Please select a Branch') }}</h1>
    </div>
    @endif
</x-filament-panels::page>