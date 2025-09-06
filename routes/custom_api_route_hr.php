<?php

use App\Attendance\Services\AttendanceHandler;
use App\Http\Controllers\Api\FaceImageController;
use App\Http\Controllers\Api\HR\AttendanceController;
use App\Http\Controllers\Api\HR\EmployeeController;
use App\Http\Controllers\Api\HR\EmployeePeriodHistoryController;
use App\Http\Controllers\Api\HR\ImageRecognize\EmployeeIdentificationController;
use App\Http\Controllers\Api\HR\ImageRecognize\LivenessController;
use App\Http\Controllers\API\HR\PayrollSimulationController;
use App\Http\Controllers\AWS\EmployeeLivenessController;
use App\Http\Controllers\Api\HR\RunPayrollController;
use App\Models\EmployeeFaceData;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Route;

Route::prefix('hr/payroll')
    ->middleware('auth:api')
    ->group(function () {
        Route::post('simulate-salaries/by-employee-ids', [PayrollSimulationController::class, 'simulateSalariesByEmployeeIds']);
        Route::post('/preview', [PayrollSimulationController::class, 'previewByBranchYearMonth']);

        // محاكاة الرواتب (بدون حفظ)
        Route::post('/simulate', [RunPayrollController::class, 'simulate']);

        // تشغيل وحفظ الرواتب
        Route::post('/run', [RunPayrollController::class, 'run']);
    });
Route::prefix('hr')
    ->group(function () {
        Route::post('/attendance/store', [AttendanceController::class, 'store'])->middleware('auth:api');
        // يمكنك إضافة المزيد لاحقًا مثل:
        // Route::get('/employee/{id}', [EmployeeController::class, 'show']);

        Route::get('employees/{employee}/periodsHistory', [EmployeePeriodHistoryController::class, 'getPeriodsByDateRange']);
        Route::get('/employeeAttendance', [AttendanceController::class, 'employeeAttendance']);
        Route::get('employeesAttendanceOnDate', [AttendanceController::class, 'employeesAttendanceOnDate']);

        Route::post('/faceRecognition', [AttendanceController::class, 'identifyEmployeeFromImage']);
        Route::post('/identifyEmployee', [EmployeeIdentificationController::class, 'identify'])
            // ->name('employees.identify')
        ;
        Route::post('/liveness', [LivenessController::class, 'check']);
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
    $minFaces = request('min_faces', 1);
    return EmployeeFaceData::active()
        ->faceAdded()
        ->get([
            'employee_id',
            'employee_name',
            'employee_email',
            'employee_branch_id',
            'embedding',
        ])
        ->groupBy('employee_id')
        ->filter(fn(Collection $employeeFaceGroup) => $employeeFaceGroup->count() >= $minFaces)
        ->map(function ($group) {
            $first = $group->first();
            return [
                'employee_id'        => $first->employee_id,
                'employee_name'      => $first->employee_name,
                'employee_email'     => $first->employee_email,
                'employee_branch_id' => $first->employee_branch_id,
                'embeddings'         => $group->pluck('embedding')->values(),
            ];
        })
        ->values();
    Route::post('/attendance/store', [AttendanceController::class, 'store'])->middleware('auth:api');
    // يمكنك إضافة المزيد لاحقًا مثل:
    // Route::get('/employee/{id}', [EmployeeController::class, 'show']);
    Route::post('/faceRecognition', [AttendanceController::class, 'identifyEmployeeFromImage']);
});


Route::get('/test-attendance', function () {
    $result = app(AttendanceHandler::class)->handle(
        employeeId: 1,
        periodId: 1,
        deviceId: 'DEV-01',
        now: CarbonImmutable::now('Asia/Aden'),
        isRequest: false
    );
    return response()->json($result);
});