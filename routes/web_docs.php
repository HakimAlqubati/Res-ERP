<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;


Route::get('/orders-settings', function () {
    return view('docs.web.orders_status', [
        'title' => 'إعدادات حركة المخزون',
        'scenarios' => [
            'drivers' => [
                ['status' => 'جاهز للتسليم', 'action' => 'لا يحدث تأثير على المخزون'],
                ['status' => 'في الطريق', 'action' => 'خصم من المخزن الرئيسي'],
                ['status' => 'تم التسليم من مدير الفرع', 'action' => 'إدخال في مخزن الفرع'],
            ],
            'internal' => [
                ['status' => 'جاهز للتسليم', 'action' => 'خصم من المخزن الرئيسي'],
                ['status' => 'تم التسليم من مدير الفرع', 'action' => 'إدخال في مخزن الفرع'],
            ],
        ],
    ]);
});



Route::get('/redis-test', function () {
    // تسجيل وقت بداية تنفيذ الكود
    $startTime = microtime(true);

    // استخدام دالة remember التي تبحث في الـ RAM أولاً
    // الرقم 10 يعني: احفظ هذه البيانات لمدة 10 ثوانٍ فقط (لتفريغ الرام تلقائياً)
    $campaignsData = Cache::remember('waseed_active_campaigns_stats', 10, function () {

        // الدالة بالداخل لن يتم تنفيذها إلا إذا لم تكن البيانات موجودة في الكاش
        // سنستخدم sleep(3) لمحاكاة استعلام بطيء جداً من قاعدة البيانات يستغرق 3 ثوانٍ
        sleep(3);

        return [
            'total_campaigns' => 1500,
            'messages_sent' => 45000,
            'status' => 'Stable'
        ];
    });

    // حساب الوقت المستغرق
    $executionTime = round(microtime(true) - $startTime, 4);

    return response()->json([
        'message' => 'Test successful!',
        'execution_time_seconds' => $executionTime,
        'data' => $campaignsData
    ]);
});
