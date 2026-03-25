<?php

use Illuminate\Support\Facades\Route;
use App\Modules\HR\Payroll\Http\Controllers\PayrollSimulationController;
use App\Modules\HR\Payroll\Http\Controllers\RunPayrollController;
use App\Modules\HR\Payroll\Http\Controllers\PayrollApiController;

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
        // جلب قائمة الرواتب
        Route::get('/', [PayrollApiController::class, 'index']);

        // جلب تفاصيل راتب محدد
        Route::get('/{id}', [PayrollApiController::class, 'show'])->where('id', '[0-9]+');

        // محاكاة الرواتب لمجموعة موظفين
        Route::post('simulate-salaries/by-employee-ids', [PayrollSimulationController::class, 'simulateSalariesByEmployeeIds']);

        // معاينة الرواتب لفرع/سنة/شهر
        Route::post('/preview', [PayrollSimulationController::class, 'previewByBranchYearMonth']);

        // محاكاة الرواتب (بدون حفظ)
        Route::post('/simulate', [RunPayrollController::class, 'simulate']);

        // تشغيل وحفظ الرواتب
        Route::post('/run', [RunPayrollController::class, 'run']);

        // Salary Slip JSON
        Route::get('/salary-slip/{payroll_id}', [\App\Modules\HR\Payroll\Reports\SalarySlipReport::class, 'json']);
    });

Route::middleware(['web'])
    ->group(function () {
        Route::get('/admin/salary-slip/pdf/{payroll_id}', [\App\Modules\HR\Payroll\Reports\SalarySlipReport::class, 'generate'])
            ->name('salarySlip.pdf');

        // Documentation Routes
        Route::get('/payroll/logic-flow', [\App\Modules\HR\Payroll\Http\Controllers\PayrollDocsController::class, 'logicFlow'])
            ->name('payroll.docs.logic-flow');

        // Tax Calculation Explanation
        Route::get('/payroll/tax-calculation-steps', [\App\Modules\HR\Payroll\Http\Controllers\PayrollSimulationController::class, 'taxCalculationSteps'])
            ->name('payroll.docs.tax-steps');
    });

// Web Simulation Routes
// Route::prefix('payroll/simulation')->group(function () {
// محاكاة لمجموعة موظفين
Route::get('/payroll/simulation/by-employees', [\App\Modules\HR\Payroll\Http\Controllers\PayrollWebController::class, 'simulateSalariesByEmployeeIds'])
    ->name('payroll.web.simulate.employees');

// معاينة الرواتب (Preview)
Route::any('/payroll/simulation/preview', [\App\Modules\HR\Payroll\Http\Controllers\PayrollWebController::class, 'previewByBranchYearMonth'])
    ->name('payroll.web.preview');

// محاكاة التشغيل (Run)
Route::any('/payroll/simulation/run', [\App\Modules\HR\Payroll\Http\Controllers\PayrollWebController::class, 'simulateRun'])
    ->name('payroll.web.simulate.run');
// });
