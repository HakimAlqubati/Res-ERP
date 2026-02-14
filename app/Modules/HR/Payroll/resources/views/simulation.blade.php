@extends('payroll::layout')

@section('title', 'Salary Simulation (Selected Employees)')
@section('header', 'Salary Simulation')
@section('subheader', "Year: $year | Month: $month")

@section('content')

<style>
    body {
        background-color: #020617 !important;
        /* Very dark slate */
    }

    header h1 {
        color: #818cf8 !important;
        /* Indigo 400 */
    }

    header p {
        color: #94a3b8 !important;
        /* Slate 400 */
    }

    footer {
        color: #475569 !important;
        /* Slate 600 */
    }

    .glass-panel {
        background: rgba(15, 23, 42, 0.8) !important;
        /* Slate 900 with opacity */
        border: 1px solid rgba(255, 255, 255, 0.05) !important;
        backdrop-filter: blur(12px) !important;
    }

    .bg-white {
        background-color: #0f172a !important;
    }

    .bg-gray-50 {
        background-color: #1e293b !important;
    }

    .border-gray-200 {
        border-color: #334155 !important;
    }

    .divide-gray-100>*+* {
        border-color: #334155 !important;
    }

    .text-gray-900 {
        color: #f8fafc !important;
    }

    .text-gray-800 {
        color: #f1f5f9 !important;
    }

    .text-gray-700 {
        color: #e2e8f0 !important;
    }

    .text-gray-600 {
        color: #cbd5e1 !important;
    }

    .text-gray-500 {
        color: #94a3b8 !important;
    }

    .text-gray-400 {
        color: #64748b !important;
    }

    /* Indigo Accents */
    .text-indigo-700 {
        color: #a5b4fc !important;
    }

    /* Indigo 300 */
    .bg-indigo-100 {
        background-color: #312e81 !important;
        color: #e0e7ff !important;
    }

    .bg-indigo-50 {
        background-color: rgba(49, 46, 129, 0.3) !important;
        border-color: #4338ca !important;
    }

    .text-indigo-800 {
        color: #c7d2fe !important;
    }

    /* Red/Rose Accents */
    .bg-rose-50 {
        background-color: rgba(136, 19, 55, 0.2) !important;
        border-color: #9f1239 !important;
    }

    .text-rose-600 {
        color: #fb7185 !important;
    }

    .text-rose-800 {
        color: #fecdd3 !important;
    }

    /* Green/Emerald Accents */
    .text-emerald-600 {
        color: #34d399 !important;
    }

    .text-emerald-700 {
        color: #6ee7b7 !important;
    }

    /* Red/Green Status */
    .text-green-600 {
        color: #4ade80 !important;
    }

    /* Green 400 */
    .text-red-500 {
        color: #f87171 !important;
    }

    /* Red 400 */

    /* Table Hover */
    .hover\:bg-gray-50:hover {
        background-color: #1e293b !important;
    }
</style>


@php
$totalNet = 0;
foreach($results as $r) {
if(($r['success']??false) && isset($r['data']['net_salary'])) {
$totalNet += $r['data']['net_salary'];
}
}
@endphp

<!-- Totals Summary -->
<div class="glass-panel p-6 rounded-2xl mb-8 flex justify-between items-center border-l-4 border-indigo-500">
    <div>
        <h3 class="text-gray-500 text-sm font-medium">Total Net for Selected</h3>
        <p class="text-3xl font-bold text-indigo-700 mt-2">{{ number_format($totalNet, 2) }} <span class="text-sm font-normal text-gray-400"></span></p>
    </div>
    <div class="text-right">
        <span class="block text-sm text-gray-500">Employee Count</span>
        <span class="block text-2xl font-bold text-gray-800">{{ count($results) }}</span>
    </div>
</div>

<div class="space-y-8">
    @forelse($results as $row)
    @php
    $data = $row['data'] ?? [];
    $success = $row['success'] ?? false;
    $transactions = $data['transactions'] ?? [];
    @endphp

    <div class="glass-panel rounded-2xl overflow-hidden">
        <!-- Employee Header -->
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold">
                    {{ $row['employee_id'] ?? '?' }}
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-900">{{ $row['name'] ?? 'Unknown Name' }}</h2>
                    <span class="text-xs text-gray-500">ID: #{{ $row['employee_no'] ?? $row['employee_id'] ?? '-' }}</span>
                </div>
            </div>
            <div class="text-right">
                @if($success)
                <div class="text-2xl font-bold text-indigo-700">{{ number_format($data['net_salary'] ?? 0, 2) }} <span class="text-xs text-gray-500 font-normal"> </span></div>
                <div class="text-xs text-green-600">Active</div>
                @else
                <div class="text-red-500 font-bold">{{ $row['error'] ?? 'Error' }}</div>
                @endif
            </div>
        </div>

        @if($success)
        <div class="p-6">
            <!-- Stats Grid -->
            <!-- Calculation Factors & Stats -->
            <div class="grid grid-cols-2 md:grid-cols-6 gap-3 mb-6 text-sm">
                <div class="bg-gray-50 p-3 rounded border border-gray-200 col-span-1">
                    <span class="block text-gray-500 text-xs uppercase tracking-wider">Month Days</span>
                    <span class="font-mono font-bold text-lg text-gray-700">{{ $row['month_days'] ?? '-' }}</span>
                </div>
                <div class="bg-indigo-50 p-3 rounded border border-indigo-200 col-span-1">
                    <span class="block text-indigo-800 text-xs uppercase tracking-wider">Working Days</span>
                    <span class="font-mono font-bold text-lg text-indigo-700">{{ $row['working_days'] ?? '-' }}</span>
                </div>
                <div class="bg-white p-3 rounded border border-gray-200 col-span-1">
                    <span class="block text-gray-500 text-xs uppercase tracking-wider">Daily Rate</span>
                    <span class="font-mono font-bold text-lg text-emerald-600">{{ number_format($row['daily_salary'] ?? 0, 2) }}</span>
                </div>
                <div class="bg-white p-3 rounded border border-gray-200 col-span-1">
                    <span class="block text-gray-500 text-xs uppercase tracking-wider">Hourly Rate</span>
                    <span class="font-mono font-bold text-lg text-emerald-600">{{ number_format($row['hourly_salary'] ?? 0, 2) }}</span>
                </div>

                <div class="bg-rose-50 p-3 rounded border border-rose-100 col-span-1">
                    <span class="block text-rose-800 text-xs uppercase tracking-wider">Absent Days</span>

                    <span class="font-mono font-bold text-lg text-rose-600">{{ $row['attendance_statistics']['absent'] ?? 0 }}</span>
                </div>
                <div class="bg-rose-50 p-3 rounded border border-rose-100 col-span-1">
                    <span class="block text-rose-800 text-xs uppercase tracking-wider">Late Hours</span>
                    <span class="font-mono font-bold text-lg text-rose-600">{{ $row['late_hours'] ?? 0 }}</span>
                </div>
            </div>

            <!-- Transactions Table -->
            <h3 class="text-sm font-bold text-gray-600 mb-3 border-b pb-2">Salary Transactions Breakdown</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-500 font-medium">
                        <tr>
                            <th class="px-4 py-2">Type</th>
                            <th class="px-4 py-2">Details</th>
                            <th class="px-4 py-2 text-center">Unit</th>
                            <th class="px-4 py-2 text-center">Qty</th>
                            <th class="px-4 py-2 text-right">Rate</th>
                            <th class="px-4 py-2 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($transactions as $trans)
                        @php
                        $isDeduction = ($trans['operation'] ?? '+') === '-';
                        $amountClass = $isDeduction ? 'text-rose-600' : 'text-emerald-600';
                        $typeObj = $trans['type'] ?? null;
                        $typeName = is_object($typeObj) && property_exists($typeObj, 'value') ? ucfirst($typeObj->value) : 'Item';
                        @endphp
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                {{ $typeName }}
                            </td>
                            <td class="px-4 py-2">
                                <div class="font-medium text-gray-800">{{ $trans['description'] ?? '-' }}</div>
                                @if(isset($trans['sub_type']))
                                <div class="text-xs text-gray-400">
                                    {{ is_object($trans['sub_type']) && property_exists($trans['sub_type'], 'value') ? $trans['sub_type']->value : '' }}
                                </div>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-center text-xs text-gray-500">{{ $trans['unit'] ?? '-' }}</td>
                            <td class="px-4 py-2 text-center font-mono">{{ $trans['qty'] ?? 1 }}</td>
                            <td class="px-4 py-2 text-right font-mono text-gray-500">{{ number_format($trans['rate'] ?? 0, 2) }}</td>
                            <td class="px-4 py-2 text-right font-bold font-mono {{ $amountClass }}">
                                {{ number_format($trans['amount'] ?? 0, 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-4 text-center text-gray-400">No transactions recorded.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-gray-50 border-t border-gray-200 font-bold">
                        <tr>
                            <td colspan="5" class="px-4 py-2 text-right text-gray-800">Gross Salary</td>
                            <td class="px-4 py-2 text-right text-emerald-700">{{ number_format($data['gross_salary'] ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td colspan="5" class="px-4 py-2 text-right text-gray-800">Net Payable</td>
                            <td class="px-4 py-2 text-right text-indigo-700 border-t border-gray-300">{{ number_format($data['net_salary'] ?? 0, 2) }}</td>
                        </tr>
                        @if(($data['carry_forwarded'] ?? 0) > 0)
                        <tr>
                            <td colspan="5" class="px-4 py-2 text-right text-rose-600 font-bold">Carry Forwarded</td>
                            <td class="px-4 py-2 text-right text-rose-600 font-bold">{{ number_format($data['carry_forwarded'], 2) }}</td>
                        </tr>
                        @endif
                    </tfoot>
                </table>
            </div>
        </div>
        @endif
    </div>
    @empty
    <div class="text-center p-12 text-gray-400 bg-white rounded-2xl shadow-sm">
        No employees found or simulation failed used provided criteria.
    </div>
    @endforelse
</div>
@endsection