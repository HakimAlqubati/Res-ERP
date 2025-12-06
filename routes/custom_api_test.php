<?php 

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/debug-inventory-sql', function () {
    // 1. إعدادات التجربة (ضع نفس القيم التي تسبب المشكلة)
    // ==========================================
    $storeId    = 1;      // غير هذا الرقم
    $categoryId = 5;      // غير هذا الرقم
    $fromDate   = '2023-01-01';
    $toDate     = '2023-12-31';
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