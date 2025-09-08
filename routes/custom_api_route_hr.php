<?php

use App\Http\Controllers\Api\HR\AttendanceController;
use Illuminate\Support\Facades\Log;
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


