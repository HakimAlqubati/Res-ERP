<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/debug-inventory-sql', function () {
    // 1. إعدادات التجربة (ضع نفس القيم التي تسبب المشكلة)
    // ==========================================
    $storeId    = 10;      // غير هذا الرقم
    $categoryId = 7;      // غير هذا الرقم
    $fromDate   = '2025-11-01 00:00:00';
    $toDate     = '2025-11-03 23:59:59';
    // ==========================================

    // تفعيل تسجيل الاستعلامات لرؤية الـ SQL الخام
    DB::enableQueryLog();

    // 2. فحص متغيرات البيئة الحساسة
    $diagnostics = [
        'app_locale' => app()->getLocale(),
        'php_timezone' => date_default_timezone_get(),
        'app_config_timezone' => config('app.timezone'),
        'database_connection' => config('database.default'),
        // فحص وضع الـ SQL Mode (المشتبه به الأول)
        'mysql_sql_mode' => DB::select("SELECT @@sql_mode as mode")[0]->mode,
        // فحص إصدار قاعدة البيانات
        'mysql_version' => DB::select("SELECT VERSION() as v")[0]->v,
    ];

    // 3. استدعاء الدالة الخاصة بك
    // افترض أن الدالة موجودة في كلاس اسمه InventoryService أو Controller
    // سأقوم بمحاكاة استدعاء الدالة هنا مباشرة لغرض الفحص

    // (قم باستدعاء دالتك هنا، مثال:)
    // $controller = new \App\Http\Controllers\YourController();
    // $results = $controller->runSourceBalanceByCategorySQL($storeId, $categoryId, $fromDate, $toDate);

    // *ملاحظة:* إذا لم تستطع استدعاء الكلاس، يمكنك نسخ كود الدالة ولصقه هنا مؤقتاً للتجربة

    // ... لنفترض أننا نفذنا الكود وحصلنا على النتائج في $results ...
    // سأضع مصفوفة فارغة هنا كمثال حتى تقوم أنت بوضع استدعاء دالتك
    $results = "قم بوضع استدعاء دالتك هنا في الكود";


    // Fix: Invoke the method from the class instance using the helper function app()
    $results = app(\App\Filament\Resources\OrderReportsResource\Pages\GeneralReportProductDetails::class)
        ->runSourceBalanceByCategorySQL($storeId, $categoryId, $fromDate, $toDate);
    // 4. جلب الاستعلام الذي تم تنفيذه
    $queryLog = DB::getQueryLog();
    $lastQuery = end($queryLog);

    // 5. طباعة التقرير الشامل JSON
    return response()->json([
        'environment_diagnostics' => $diagnostics,
        'executed_query' => $lastQuery, // سيعطيك الـ SQL والـ Bindings
        'data_count' => is_array($results) ? count($results) : 0,
        'data_sample' => is_array($results) ? array_slice($results, 0, 3) : $results, // أول 3 نتائج فقط
    ]);
});


Route::get('/test/pendingApplications', function () {
    $filters = [
        'year'         => request('year', now()->year),
        'month'        => request('month', now()->month),
        'day'          => request('day'),          // اختياري: فلترة بيوم محدد
        'branch_id'    => request('branch_id'),
        'employee_ids' => request('employee_ids') ? explode(',', request('employee_ids')) : null,
    ];

    $checker = app(\App\Modules\HR\EmployeeApplications\Checker\MonthlyPendingApplicationChecker::class);

    $summary = $checker->getDashboardSummary($filters);

    return response()->json([
        'status'  => 'success',
        'filters' => $filters,
        'result'  => $summary,
    ]);
});

Route::get('/test/clearAttendanceCache', function () {
    $date = request('date', now()->toDateString());
    
    $startTime = microtime(true);
    clearAllEmployeesDailyAttendanceCache($date);
    $executionTime = round(microtime(true) - $startTime, 10);
    
    return response()->json([
        'status' => 'success',
        'message' => "Cleared daily attendance cache for all employees on {$date}",
        'execution_time_seconds' => $executionTime,
    ]);
});

Route::get('/test/clearAttendanceCacheForMonth', function () {
    $year = request('year', now()->year);
    $month = request('month', now()->month);
    
    $startTime = microtime(true);
    clearAllEmployeesDailyAttendanceCacheForMonth($year, $month);
    $executionTime = round(microtime(true) - $startTime, 4);
    
    return response()->json([
        'status' => 'success',
        'message' => "Cleared daily attendance cache for all employees for {$year}-{$month}",
        'execution_time_seconds' => $executionTime,
    ]);
});

Route::match(['get', 'post'], '/test/checkPriceChange', function () {
    $items = request('items');

    if (!is_array($items) || empty($items)) {
        return response()->json([
            'status' => 'error',
            'message' => 'Missing or invalid items array. Please send an "items" array where each item contains: product_id, unit_id, price, and optionally package_size.'
        ], 400);
    }

    $results = \App\Modules\Stock\PriceValidation\Services\PriceChecker::checkMany($items);
return $results;
    $formattedResults = [];
    foreach ($results as $key => $result) {
        $formattedResults[$key] = [
            'raw_data' => $result->toArray(),
            'summary'  => $result->toSummary(),
        ];
    }

    return response()->json([
        'results' => $formattedResults,
    ]);
});
