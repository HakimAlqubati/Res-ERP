<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Accounting\ForTesting\Controllers\AccountingTestController;

Route::middleware('web')->group(function () {
    Route::get('/accounting/test/tree', [AccountingTestController::class, 'index'])->name('accounting.test.tree');
});
