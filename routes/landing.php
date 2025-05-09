<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Landing\WorkbenchController;

Route::get('/workbench-erp', [WorkbenchController::class, 'show'])->name('landing.workbench');
