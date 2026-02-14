@extends('payroll::layout')

@section('title', 'محاكاة التشغيل الكامل')
@section('header', 'نتيجة محاكاة التشغيل (Run Simulation)')
@section('subheader', 'عرض النتائج المتوقعة لعملية التشغيل')

@section('content')

{{-- Check structure of $data from Runner --}}
@php
// Usually Runner returns ['success'=>true, 'data'=>[...], 'meta'=>...] or similar depending on implementation
// If we passed the raw array from service:
$items = $data['results'] ?? $data['data'] ?? [];
// If it's the structure from PayrollRunnerInterface::simulate

// Let's assume standard structure, if array is flat list of employees or contained in key
if(isset($data[0]) && is_array($data[0])) {
$items = $data;
}
@endphp

<div class="glass-panel p-6 rounded-2xl mb-8">
    <h2 class="text-xl font-bold mb-4">ملخص المحاكاة</h2>
    <div class="bg-blue-50 text-blue-800 p-4 rounded-lg">
        هذه محاكاة لعملية التشغيل. البيانات لم يتم حفظها في قاعدة البيانات.
    </div>
</div>

<div class="glass-panel rounded-2xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-right">
            <thead class="bg-gray-50 text-gray-600 font-medium border-b border-gray-200">
                <tr>
                    <th class="px-6 py-4">الموظف</th>
                    <th class="px-6 py-4">الراتب المتوقع</th>
                    <th class="px-6 py-4">الملاحظات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($items as $row)
                @php
                $empData = $row['data'] ?? [];
                @endphp
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4 font-medium text-gray-900">
                        #{{ $row['employee_id'] ?? ($empData['employee_id'] ?? '-') }}
                    </td>
                    <td class="px-6 py-4 font-bold text-indigo-700">
                        {{ number_format($empData['net_salary'] ?? 0, 2) }}
                    </td>
                    <td class="px-6 py-4 text-gray-500">
                        {{ $row['status'] ?? 'Success' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="px-6 py-8 text-center text-gray-400">
                        {{ json_encode($data) }} <!-- Fallback debug if empty or structure differs -->
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection