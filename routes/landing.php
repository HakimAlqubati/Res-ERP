<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Landing\WorkbenchController;

Route::get('/workbench-erp', [WorkbenchController::class, 'show'])->name('landing.workbench');
Route::get('/restaurant-erp', [WorkbenchController::class, 'restaurantErp'])->name('landing.restaurant-erp');
Route::get('/faq', [WorkbenchController::class, 'faq'])->name('landing.faq');
