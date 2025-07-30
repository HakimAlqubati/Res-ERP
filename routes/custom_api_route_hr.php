<?php

use App\Http\Controllers\Api\HR\AttendanceController;
use Illuminate\Support\Facades\Route;

Route::prefix('hr')
    ->group(function () {
    Route::post('/attendance/store', [AttendanceController::class, 'store'])->middleware('auth:api');
    // يمكنك إضافة المزيد لاحقًا مثل:
    // Route::get('/employee/{id}', [EmployeeController::class, 'show']);
    Route::post('/faceRecognition', [AttendanceController::class, 'identifyEmployeeFromImage']);

});