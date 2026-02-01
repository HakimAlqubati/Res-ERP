@extends('hr.payroll.layout')

@section('title', 'Salary Simulation (Selected Employees)')
@section('header', 'Salary Simulation')
@section('subheader', "Year: $year | Month: $month")

@section('content')

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
        <p class="text-3xl font-bold text-indigo-700 mt-2">{{ number_format($totalNet, 2) }} <span class="text-sm font-normal text-gray-400">SAR</span></p>
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
                <div class="text-2xl font-bold text-indigo-700">{{ number_format($data['net_salary'] ?? 0, 2) }} <span class="text-xs text-gray-500 font-normal">SAR</span></div>
                <div class="text-xs text-green-600">Active</div>
                @else
                <div class="text-red-500 font-bold">{{ $row['error'] ?? 'Error' }}</div>
                @endif
            </div>
        </div>

        @if($success)
        <div class="p-6">
            <!-- Stats Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 text-sm">
                <div class="bg-white p-3 rounded border border-gray-100">
                    <span class="block text-gray-400 text-xs">Base Salary</span>
                    <span class="font-bold text-gray-800">{{ number_format($data['base_salary'] ?? 0, 2) }}</span>
                </div>
                <div class="bg-white p-3 rounded border border-gray-100">
                    <span class="block text-gray-400 text-xs">Working Days</span>
                    <span class="font-bold text-gray-800">{{ $row['attendance_statistics']['present_days'] ?? '-' }} / {{ $row['month_days'] ?? '-' }}</span>
                </div>
                <div class="bg-white p-3 rounded border border-gray-100">
                    <span class="block text-gray-400 text-xs">Absence Deduction</span>
                    <span class="font-bold text-rose-600">{{ number_format($data['absence_deduction'] ?? 0, 2) }}</span>
                </div>
                <div class="bg-white p-3 rounded border border-gray-100">
                    <span class="block text-gray-400 text-xs">Late Deduction</span>
                    <span class="font-bold text-rose-600">{{ number_format($data['late_deduction'] ?? 0, 2) }}</span>
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