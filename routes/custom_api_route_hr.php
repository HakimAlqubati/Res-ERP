<?php

use App\Http\Controllers\Api\FaceImageController;
use App\Http\Controllers\Api\HR\AttendanceController;
use App\Http\Controllers\Api\HR\EmployeeController;
use App\Http\Controllers\Api\HR\EmployeePeriodHistoryController;
use App\Http\Controllers\AWS\EmployeeLivenessController;
use Illuminate\Support\Facades\Route;

Route::prefix('hr')
    ->group(function () {
        Route::post('/attendance/store', [AttendanceController::class, 'store'])->middleware('auth:api');
        // يمكنك إضافة المزيد لاحقًا مثل:
        // Route::get('/employee/{id}', [EmployeeController::class, 'show']);

        Route::get('employees/{employee}/periodsHistory', [EmployeePeriodHistoryController::class, 'getPeriodsByDateRange']);
        Route::get('/employeeAttendance', [AttendanceController::class, 'employeeAttendance']);
        Route::get('employeesAttendanceOnDate', [AttendanceController::class, 'employeesAttendanceOnDate']);

    });

Route::prefix('aws/employee-liveness')->group(function () {
    // بدء جلسة التحقق (startSession)
    Route::post('/start-session', [EmployeeLivenessController::class, 'startSession']);

    // التحقق من نتيجة الجلسة (checkSession)
    Route::get('/check-session', [EmployeeLivenessController::class, 'checkSession']);
});

Route::get('employees/simple-list', [EmployeeController::class, 'simpleList']);

Route::post('/face-images', [FaceImageController::class, 'store']);