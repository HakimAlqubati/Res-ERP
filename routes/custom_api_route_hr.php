<?php

use App\Http\Controllers\Api\FaceImageController;
use App\Http\Controllers\Api\HR\AttendanceController;
use App\Http\Controllers\Api\HR\EmployeeController;
use App\Http\Controllers\Api\HR\EmployeePeriodHistoryController;
use App\Http\Controllers\AWS\EmployeeLivenessController;
use App\Models\EmployeeFaceData;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

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


Route::get('/face-data', function () {
    return EmployeeFaceData::active()
        ->get([
            'employee_id',
            'employee_name',
            'employee_email',
            'employee_branch_id',
            'embedding',
        ])
        ->groupBy('employee_id')
        ->map(function ($group) {
            $first = $group->first();
            return [
                'employee_id'       => $first->employee_id,
                'employee_name'     => $first->employee_name,
                'employee_email'    => $first->employee_email,
                'employee_branch_id'=> $first->employee_branch_id,
                'embeddings'        => $group->pluck('embedding')->values(),
            ];
        })
        ->values();
});


Route::get('/face-data-with-urls', function () {
    return EmployeeFaceData::active()
        ->get([
            'employee_id',
            'employee_name',
            'employee_email',
            'employee_branch_id',
            'image_path', // ← افترضنا أن الصورة محفوظة في هذا الحقل
        ])
        ->groupBy('employee_id')
        ->map(function ($group) {
            $first = $group->first();

            return [
                'employee_id'        => $first->employee_id,
                'employee_name'      => $first->employee_name,
                'employee_email'     => $first->employee_email,
                'employee_branch_id' => $first->employee_branch_id,
                'image_urls'         => $group->pluck('image_path')->map(function ($path) {
                    return Storage::url($path); // ← يرجع رابط الصورة
                })->values(),
            ];
        })
        ->values();
});