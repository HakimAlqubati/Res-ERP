@extends('payroll::layout')

@section('content')
<div class="max-w-4xl mx-auto py-10 px-4 sm:px-6 lg:px-8" dir="rtl">
    <div class="text-center mb-12">
        <h1 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
            كيف يتم حساب ضريبة الدخل (MTD)؟
        </h1>
        <p class="mt-4 text-xl text-gray-500">
            شرح مبسط للخطوات التي يتبعها النظام للوصول للقسط الشهري المستحق.
        </p>
    </div>

    <div class="space-y-6">
        @foreach($steps as $index => $step)
        <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-start">
                <div class="flex-shrink-0 ml-4">
                    <span class="flex items-center justify-center h-8 w-8 rounded-full bg-slate-100 text-slate-600 font-bold text-sm">
                        {{ $index + 1 }}
                    </span>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-gray-900 mb-2">
                        {{ Str::after($step->title, '. ') }}
                    </h3>
                    <p class="text-gray-600 leading-relaxed whitespace-pre-line text-sm">
                        {{ $step->description }}
                    </p>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Final Note -->
    <div class="mt-16 p-6 bg-slate-50 rounded-2xl border border-slate-200 text-center">
        <p class="text-slate-600">
            النظام يقوم بهذه العمليات آلياً في كل مرة يتم فيها احتساب كشف الراتب، لضمان الدقة والامتثال لأحدث جداول LHDN.
        </p>
    </div>
</div>
@endsection