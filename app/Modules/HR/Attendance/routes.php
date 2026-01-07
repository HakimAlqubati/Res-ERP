<?php

/**
 * Routes لوحدة الحضور
 * 
 * يتم تضمين هذا الملف من api.php
 */

use App\Modules\HR\Attendance\Http\Controllers\AttendanceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Attendance Module API Routes
|--------------------------------------------------------------------------
|
| مجموعة الـ API routes الخاصة بوحدة الحضور
|
*/

Route::prefix('api/v2/hr/attendance')
    ->middleware(['auth:api'])
    ->group(function () {

        // تسجيل حضور/انصراف
        Route::post('/', [AttendanceController::class, 'store']);

        // اختبار (للتوافق مع V2)
        Route::post('/test', [AttendanceController::class, 'test']);

        // توليد سجلات حضور جماعية
        Route::post('/bulk-generate', [AttendanceController::class, 'bulkGenerate']);
    });
