<?php

use Illuminate\Support\Facades\Route;
use App\Modules\HR\Payroll\Http\Controllers\PayrollSimulationController;
use App\Modules\HR\Payroll\Http\Controllers\RunPayrollController;

/*
|--------------------------------------------------------------------------
| Payroll Module API Routes
|--------------------------------------------------------------------------
|
| All routes are prefixed with 'api/hr/payroll' and protected by auth:api middleware.
|
*/

Route::prefix('api/hr/payroll')
    ->middleware('auth:api')
    ->group(function () {
        // محاكاة الرواتب لمجموعة موظفين
        Route::post('simulate-salaries/by-employee-ids', [PayrollSimulationController::class, 'simulateSalariesByEmployeeIds']);

        // معاينة الرواتب لفرع/سنة/شهر
        Route::post('/preview', [PayrollSimulationController::class, 'previewByBranchYearMonth']);

        // محاكاة الرواتب (بدون حفظ)
        Route::post('/simulate', [RunPayrollController::class, 'simulate']);

        // تشغيل وحفظ الرواتب
        Route::post('/run', [RunPayrollController::class, 'run']);
    });
