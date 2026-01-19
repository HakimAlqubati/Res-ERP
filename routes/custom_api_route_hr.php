<?php

use App\Http\Controllers\Api\FaceImageController;
use App\Http\Controllers\Api\HR\AttendanceController;
use App\Http\Controllers\Api\HR\EmployeeApplicationController;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Api\HR\EmployeeController;
use App\Http\Controllers\Api\HR\EmployeePeriodHistoryController;
use App\Http\Controllers\Api\HR\ImageRecognize\EmployeeIdentificationController;
use App\Http\Controllers\Api\HR\ImageRecognize\LivenessController;
use App\Http\Controllers\API\HR\PayrollSimulationController;
use App\Http\Controllers\AWS\EmployeeLivenessController;
use App\Http\Controllers\Api\HR\RunPayrollController;
use App\Http\Controllers\Api\HR\TaskCommentController;
use App\Http\Controllers\Api\HR\TaskController;
use App\Http\Controllers\Api\HR\TaskLogController;
use App\Http\Controllers\Api\HR\TaskStepController;
use App\Http\Controllers\Api\V1\EquipmentController;
use App\Http\Controllers\Api\V1\EquipmentLogController;
use App\Http\Controllers\Api\V1\MaintenanceController;
use App\Models\EmployeeFaceData;
use App\Services\HR\SalaryHelpers\WeeklyLeaveCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// Route::prefix('hr/payroll')
//     ->middleware('auth:api')
//     ->group(function () {
//         Route::post('simulate-salaries/by-employee-ids', [PayrollSimulationController::class, 'simulateSalariesByEmployeeIds']);
//         Route::post('/preview', [PayrollSimulationController::class, 'previewByBranchYearMonth']);

//         // محاكاة الرواتب (بدون حفظ)
//         Route::post('/simulate', [RunPayrollController::class, 'simulate']);

//         // تشغيل وحفظ الرواتب
//         Route::post('/run', [RunPayrollController::class, 'run']);
//     });

Route::prefix('hr')
    ->group(function () {
        // Route::post('/attendance/store', [AttendanceController::class, 'store'])->middleware('auth:api');
        // Route::post('/attendance/storeInOut', [AttendanceController::class, 'storeInOut'])->middleware('auth:api');
        // Route::post('/attendance/storeBulk', [AttendanceController::class, 'storeBulk'])->middleware('auth:api');
        // يمكنك إضافة المزيد لاحقًا مثل:
        // Route::get('/employee/{id}', [EmployeeController::class, 'show']);

        Route::get('employees/{employee}/periodsHistory', [EmployeePeriodHistoryController::class, 'getPeriodsByDateRange']);
        Route::get('/employeeAttendance', [AttendanceController::class, 'employeeAttendance']);
        Route::get('employeesAttendanceOnDate', [AttendanceController::class, 'employeesAttendanceOnDate']);

        Route::get('/attendancePlan', [AttendanceController::class, 'generate']);

        // Route::post('/attendance/plan/execute', [AttendancePlanController::class, 'execute'])->middleware('auth:api');
        Route::post('/faceRecognition', [AttendanceController::class, 'identifyEmployeeFromImage']);
        Route::post('/identifyEmployee', [EmployeeIdentificationController::class, 'identify'])
            // ->name('employees.identify')
            ->middleware('auth:api')
        ;
        Route::post('/liveness', [LivenessController::class, 'check']);
    });

Route::prefix('applications')
    ->middleware('auth:api')
    ->group(function () {
        Route::get('/types', [EmployeeApplicationController::class, 'getTypes']); // ✅ الأنواع

        Route::get('/', [EmployeeApplicationController::class, 'index']); // GET /applications
        Route::post('/', [EmployeeApplicationController::class, 'store']); // POST /applications
        Route::get('/{id}', [EmployeeApplicationController::class, 'show']); // GET /applications/{id}
        Route::put('/{id}', [EmployeeApplicationController::class, 'update']); // PUT /applications/{id}
        Route::delete('/{id}', [EmployeeApplicationController::class, 'destroy']); // DELETE /applications/{id}

        // Actions
        Route::post('/{id}/approve', [EmployeeApplicationController::class, 'approve']); // POST /applications/{id}/approve
        Route::post('/{id}/reject', [EmployeeApplicationController::class, 'reject']);   // POST /applications/{id}/reject


    });


Route::middleware(['auth:api'])
    // ->prefix('tasks')
    ->group(function () {

        // Tasks
        Route::get('tasks', [TaskController::class, 'index']);
        Route::post('tasks', [TaskController::class, 'store']);
        Route::get('tasks/{task}', [TaskController::class, 'show'])->name('tasks.show')->whereNumber('task');
        Route::put('tasks/{task}', [TaskController::class, 'update']);
        Route::delete('tasks/{task}', [TaskController::class, 'destroy']);
        Route::post('tasks/{task}/photos', [TaskController::class, 'storePhotos'])
            ->name('tasks.photos.store')
            ->whereNumber('task');

        Route::get('tasks/statuses', [TaskController::class, 'getStatuses']);
        Route::get('tasks/statusesColors', [TaskController::class, 'getStatusColors']);
        Route::get('tasks/{task}/nextStatuses', [TaskController::class, 'getNextStatuses']);

        // Transitions
        Route::post('tasks/{task}/move', [TaskController::class, 'move']);      // انتقال الحالة
        Route::post('tasks/{task}/reject', [TaskController::class, 'reject']);  // رفض

        // Comments
        Route::get('tasks/{task}/comments', [TaskCommentController::class, 'index']);
        Route::post('tasks/{task}/comments', [TaskCommentController::class, 'store']);

        // Attachments
        // Route::get('tasks/{task}/attachments', [TaskAttachmentController::class, 'index']);
        // Route::post('tasks/{task}/attachments', [TaskAttachmentController::class, 'store']);
        // Route::delete('tasks/{task}/attachments/{attachment}', [TaskAttachmentController::class, 'destroy']);

        // Steps
        Route::get('tasks/{task}/steps', [TaskStepController::class, 'index']);
        Route::post('tasks/{task}/steps/{step}/toggle', [TaskStepController::class, 'toggleDone']);

        // Rating
        // Route::get('tasks/{task}/rating', [TaskRatingController::class, 'show']);
        // Route::post('tasks/{task}/rating', [TaskRatingController::class, 'store']);

        // Logs
        Route::get('tasks/{task}/logs', [TaskLogController::class, 'index']);
    });

Route::prefix('hr')
    ->middleware('auth:api')
    ->group(function () {

        Route::get('/employees/{id}/leaveBalances', [EmployeeController::class, 'leaveBalances']);
        Route::get('/employees/leaveBalances', [EmployeeController::class, 'leaveBalancesAll']);
    });
Route::prefix('aws/employee-liveness')->group(function () {
    // بدء جلسة التحقق (startSession)
    Route::post('/start-session', [EmployeeLivenessController::class, 'startSession']);

    // التحقق من نتيجة الجلسة (checkSession)
    Route::get('/check-session', [EmployeeLivenessController::class, 'checkSession']);
});


Route::prefix('v1')->middleware(['auth:api'])->group(function () {
    // Equipments
    Route::apiResource('equipments', EquipmentController::class);
    Route::post('equipments/{equipment}/service', [EquipmentController::class, 'service']);
    Route::post('equipments/{equipment}/move', [EquipmentController::class, 'move']);
    Route::post('equipments/{equipment}/retire', [EquipmentController::class, 'retire']);
    Route::post('equipments/{equipment}/media', [EquipmentController::class, 'uploadMedia']);

    Route::get('equipmentsTypes', [EquipmentController::class, 'equipmentTypes']);
    Route::get('equipmentsCategories', [EquipmentController::class, 'equipmentCategories']);

    // Logs
    Route::get('equipmentLogs', [EquipmentLogController::class, 'index']);
    Route::get('equipments/{equipment}/logs', [EquipmentLogController::class, 'byEquipment']);
    Route::post('equipments/{equipment}/logs', [EquipmentLogController::class, 'store']);

    // Maintenance
    Route::get('maintenance/overdue', [MaintenanceController::class, 'overdue']);
    Route::get('maintenance/dueSoon', [MaintenanceController::class, 'dueSoon']);
    Route::get('maintenance/summary', [MaintenanceController::class, 'summary']);
});

Route::prefix('v1')->middleware(['auth:api'])->group(function () {
    // Service Requests
    Route::apiResource('serviceRequests', \App\Http\Controllers\Api\V1\ServiceRequestController::class);
    Route::post('serviceRequests/{serviceRequest}/assign', [\App\Http\Controllers\Api\V1\ServiceRequestController::class, 'assign']);
    Route::post('serviceRequests/{serviceRequest}/status', [\App\Http\Controllers\Api\V1\ServiceRequestController::class, 'changeStatus']);
    Route::post('serviceRequests/{serviceRequest}/accept', [\App\Http\Controllers\Api\V1\ServiceRequestController::class, 'accept']);
    Route::post('serviceRequests/{serviceRequest}/equipment', [\App\Http\Controllers\Api\V1\ServiceRequestController::class, 'attachEquipment']);
    Route::delete('serviceRequests/{serviceRequest}/equipment', [\App\Http\Controllers\Api\V1\ServiceRequestController::class, 'detachEquipment']);
    Route::post('serviceRequests/{serviceRequest}/media', [\App\Http\Controllers\Api\V1\ServiceRequestController::class, 'uploadMedia']);
    // Service Request Photos
    Route::get('serviceRequests/{serviceRequest}/photos', [\App\Http\Controllers\Api\V1\ServiceRequestController::class, 'getPhotos'])
        ->name('serviceRequests.photos.index');

    // Comments
    Route::get('serviceRequests/{serviceRequest}/comments', [\App\Http\Controllers\Api\V1\ServiceRequestController::class, 'comments']);
    Route::post('serviceRequests/{serviceRequest}/comments', [\App\Http\Controllers\Api\V1\ServiceRequestController::class, 'addComment']);

    // Logs
    Route::get('serviceRequests/{serviceRequest}/logs', [\App\Http\Controllers\Api\V1\ServiceRequestController::class, 'logs']);
    Route::get('serviceRequestsStatuses', [\App\Http\Controllers\Api\V1\ServiceRequestController::class, 'statuses']);
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

// Route::post('/v2/attendance/test', [AttendanceController::class, 'store'])->middleware('auth:api');



Route::get('/testresult', function () {
    $nameRaw = "KHAIRALAH EBRAHIM MOHMMED ABDULLAH AL-dd-dd-dd-sdf-werw-sdf- 234sdf - SHARAEA-1";
    $parts  = explode('-', $nameRaw);
    $empId  = trim(array_pop($parts));          // آخر جزء = ID
    $name   = trim(implode('-', $parts));
    return [
        'empId' => $empId,
        'name' => $name
    ];
});

Route::get('/test-log', function () {
    // Test different log levels
    Log::info('This is an INFO level log message');
    Log::warning('This is a WARNING level log message');
    Log::error('This is an ERROR level log message');
    Log::debug('This is a DEBUG level log message');
    Log::critical('This is a CRITICAL level log message');

    // Test with context data
    Log::info('User action performed', [
        'user_id' => 123,
        'action' => 'test_log_route',
        'timestamp' => now(),
        'ip' => request()->ip()
    ]);

    return response()->json([
        'message' => 'Log test completed successfully',
        'logs_written' => [
            'info' => 'INFO level log written',
            'warning' => 'WARNING level log written',
            'error' => 'ERROR level log written',
            'debug' => 'DEBUG level log written',
            'critical' => 'CRITICAL level log written',
            'context_data' => 'INFO log with context data written'
        ],
        'check_logs_at' => storage_path('logs/laravel.log')
    ]);
});



Route::get('/testLeave', function () {
    // استقبل المتغيرات من الرابط ?required=30&absent=8
    $required = (int) request('required', 30);
    $absent   = (int) request('absent', 8);


    // $result = WeeklyLeaveCalculator::calculate($required, $absent);
    $result = WeeklyLeaveCalculator::calculateLeave($absent);

    return response()->json($result);
});


Route::get('/test-google-upload', function () {
    try {
        $folder = env('GOOGLE_DRIVE_FOLDER_ID', ''); // ضع هنا معرف المجلد الذي تريد الرفع إليه

        Storage::disk('google')->put("{$folder}/test.txt", 'Hello from Laravel inside folder ✅');
        return '✅ File uploaded to Google Drive successfully.';
    } catch (\Throwable $e) {
        return '❌ Error: ' . $e->getMessage();
    }
});


use App\Http\Controllers\Api\HR\LeaveTypeController;

Route::prefix('hr')
    ->middleware('auth:api') // drop if you want it public
    ->group(function () {
        Route::get('/leaveTypes', [LeaveTypeController::class, 'index']);                 // list + filters + pagination
        Route::get('/leaveTypes/{leaveType}', [LeaveTypeController::class, 'show']);      // single
        Route::get('/leaveTypes-weekly', [LeaveTypeController::class, 'weekly']);         // first active weekly/monthly
        Route::get('/leaveTypes-monthly-days-sum', [LeaveTypeController::class, 'monthlyDaysSum']); // sum with default=4
    });
