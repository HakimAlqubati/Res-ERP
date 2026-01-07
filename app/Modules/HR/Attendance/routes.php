<?php

/**
 * Routes لوحدة الحضور
 * 
 * يتم تحميل هذا الملف تلقائياً من AttendanceServiceProvider
 * 
 * ملاحظة: هذه الـ routes اختيارية ويمكن الاستمرار باستخدام
 * الـ routes الموجودة في custom_api_route_hr.php
 */

use App\Modules\HR\Attendance\Http\Controllers\AttendanceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Attendance Module API Routes
|--------------------------------------------------------------------------
|
| مجموعة الـ API routes الخاصة بوحدة الحضور
| يمكن تفعيلها لاحقاً عند الحاجة
|
*/

// Route::prefix('api/v3/hr/attendance')
//     ->middleware(['api', 'auth:sanctum'])
//     ->group(function () {
//         
//         // تسجيل حضور/انصراف
//         Route::post('/', [AttendanceController::class, 'store']);
//         
//         // جلب سجلات حضور موظف
//         Route::get('/employee/{employee}', [AttendanceController::class, 'employeeAttendance']);
//         
//         // جلب سجلات حضور عدة موظفين في تاريخ معين
//         Route::post('/employees-on-date', [AttendanceController::class, 'employeesAttendanceOnDate']);
//         
//     });
