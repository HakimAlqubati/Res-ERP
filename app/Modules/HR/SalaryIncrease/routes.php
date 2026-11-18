<?php

declare(strict_types=1);

use App\Modules\HR\SalaryIncrease\Http\Controllers\Api\SalaryIncreaseController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/hr/salary-increases')
    ->middleware(['api', 'auth:api'])
    ->group(function () {
        Route::get('/', [SalaryIncreaseController::class, 'index']);
        Route::get('/statuses', [SalaryIncreaseController::class, 'statuses']);
        Route::post('/', [SalaryIncreaseController::class, 'store']);
        Route::get('/{id}', [SalaryIncreaseController::class, 'show'])->whereNumber('id');
        Route::put('/{id}', [SalaryIncreaseController::class, 'update'])->whereNumber('id');
        Route::delete('/{id}', [SalaryIncreaseController::class, 'destroy'])->whereNumber('id');
        Route::post('/{id}/approve', [SalaryIncreaseController::class, 'approve'])->whereNumber('id');
        Route::post('/{id}/reject', [SalaryIncreaseController::class, 'reject'])->whereNumber('id');
    });
