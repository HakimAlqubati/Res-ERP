<?php

use App\Http\Controllers\Api\HR\AttendanceController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::prefix('hr')
    ->group(function () {
        Route::post('/attendance/store', [AttendanceController::class, 'store'])->middleware('auth:api');
        // يمكنك إضافة المزيد لاحقًا مثل:
        // Route::get('/employee/{id}', [EmployeeController::class, 'show']);
        Route::post('/faceRecognition', [AttendanceController::class, 'identifyEmployeeFromImage']);
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
