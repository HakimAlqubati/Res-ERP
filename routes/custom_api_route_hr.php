<?php

use App\Http\Controllers\API\HR\AttendanceController;
use App\Http\Controllers\Api\HR\EmployeePeriodHistoryController;
use Illuminate\Support\Facades\Route;

Route::prefix('hr')
    ->group(function () {
    Route::post('/attendance/store', [AttendanceController::class, 'store'])->middleware('auth:api');
    // يمكنك إضافة المزيد لاحقًا مثل:
    // Route::get('/employee/{id}', [EmployeeController::class, 'show']);

    Route::get('employees/{employee}/periodsHistory', [EmployeePeriodHistoryController::class, 'getPeriodsByDateRange']);
    Route::get('/employeeAttendance', [AttendanceController::class, 'employeeAttendance']);


});