<?php

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