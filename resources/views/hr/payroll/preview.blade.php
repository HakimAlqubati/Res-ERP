@extends('hr.payroll.layout')

@section('title', 'معاينة الرواتب')
@section('header', 'معاينة الرواتب')
@section('subheader', "السنة: $year | الشهر: $month | الفرع: $branchId")

@section('content')

<!-- Totals Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="glass-panel p-6 rounded-2xl border-r-4 border-indigo-500">
        <h3 class="text-gray-500 text-sm font-medium">إجمالي المستحق (الصافي)</h3>
        <p class="text-3xl font-bold text-indigo-700 mt-2">{{ number_format($totals['total_net'], 2) }}</p>
        <span class="text-xs text-gray-400">ريال</span>
    </div>
    <div class="glass-panel p-6 rounded-2xl border-r-4 border-emerald-500">
        <h3 class="text-gray-500 text-sm font-medium">إجمالي الراتب القائم (Gross)</h3>
        <p class="text-3xl font-bold text-emerald-700 mt-2">{{ number_format($totals['total_gross'], 2) }}</p>
        <span class="text-xs text-gray-400">ريال</span>
    </div>
    <div class="glass-panel p-6 rounded-2xl border-r-4 border-rose-500">
        <h3 class="text-gray-500 text-sm font-medium">إجمالي الخصومات</h3>
        <p class="text-3xl font-bold text-rose-700 mt-2">{{ number_format($totals['total_deductions'], 2) }}</p>
        <span class="text-xs text-gray-400">غياب / تأخير</span>
    </div>
    <div class="glass-panel p-6 rounded-2xl border-r-4 border-blue-400">
        <h3 class="text-gray-500 text-sm font-medium">عدد الموظفين</h3>
        <p class="text-3xl font-bold text-blue-700 mt-2">{{ $totals['count'] }}</p>
        <span class="text-xs text-gray-400">موظف</span>
    </div>
</div>

<!-- Detailed Table -->
<div class="glass-panel rounded-2xl overflow-hidden">
    <div class="p-6 border-b border-gray-100 flex justify-between items-center">
        <h2 class="text-lg font-bold text-gray-800">تفاصيل الموظفين</h2>
        {{-- Export buttons could go here --}}
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-right">
            <thead class="bg-gray-50 text-gray-600 font-medium border-b border-gray-200">
                <tr>
                    <th class="px-6 py-4">الموظف</th>
                    <th class="px-6 py-4">الراتب الأساسي</th>
                    <th class="px-6 py-4">البدلات</th>
                    <th class="px-6 py-4 text-rose-600">خصم الغياب</th>
                    <th class="px-6 py-4 text-rose-600">خصم التأخير</th>
                    <th class="px-6 py-4 text-green-600">الإضافي</th>
                    <th class="px-6 py-4 font-bold text-gray-800">الصافي</th>
                    <th class="px-6 py-4">الحالة</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($results as $row)
                @php
                $data = $row['data'] ?? [];
                $success = $row['success'] ?? false;
                $empId = $row['employee_id'] ?? '-';
                // In a real app we would load Employee models to show names,
                // but here we might only have IDs unless we preload them or the service returns them.
                // Assuming the service returns basic data.
                // Ideally we inject employee name into $results in the controller or service.
                // For now, ID is used.
                @endphp
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4 font-medium text-gray-900">
                        #{{ $empId }}
                        {{-- If we had the name, we'd put it here. --}}
                    </td>
                    @if($success)
                    <td class="px-6 py-4">{{ number_format($data['base_salary'] ?? 0, 2) }}</td>
                    <td class="px-6 py-4">{{ number_format(($data['housing_allowance'] ?? 0) + ($data['transport_allowance'] ?? 0), 2) }}</td>
                    <td class="px-6 py-4 text-rose-600 font-medium">-{{ number_format($data['absence_deduction'] ?? 0, 2) }}</td>
                    <td class="px-6 py-4 text-rose-600 font-medium">-{{ number_format($data['late_deduction'] ?? 0, 2) }}</td>
                    <td class="px-6 py-4 text-green-600 font-medium">+{{ number_format($data['overtime_amount'] ?? 0, 2) }}</td>
                    <td class="px-6 py-4 font-bold text-indigo-700 bg-indigo-50/50">
                        {{ number_format($data['net_salary'] ?? 0, 2) }}
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-700">ناجح</span>
                    </td>
                    @else
                    <td colspan="6" class="px-6 py-4 text-center text-red-500">
                        {{ $row['error'] ?? 'خطأ في الحساب' }}
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 rounded-full text-xs bg-red-100 text-red-700">فشل</span>
                    </td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-8 text-center text-gray-400">لا توجد بيانات للعرض</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection