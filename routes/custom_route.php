<?php

use App\Http\Controllers\Analytics\BranchConsumptionAnalysisController;
use App\Http\Controllers\FcmController;
use App\Http\Controllers\TestController3;
use App\Http\Controllers\TestController4;
use Illuminate\Support\Facades\Route;

Route::get('/custom-route', function () {
    return response()->json(['message' => 'Hello from custom route']);
});

Route::get('/testGetOrders', [TestController4::class, 'testGetOrders'])->middleware('auth:api');
Route::get('/testGetOrdersDetails/{orderId}', [TestController4::class, 'testGetOrdersDetails'])->middleware('auth:api');

Route::get('/testfifo', [TestController3::class, 'testFifo']);
Route::get('/testQRCode/{id}', [TestController3::class, 'testQRCode'])->name('testQRCode');

Route::get('/currntStock', [TestController3::class, 'currntStock'])->name('currntStock');
Route::get('/lowStock', [TestController3::class, 'lowStock']);

Route::get('/getProductItems/{id}', [TestController3::class, 'getProductItems']);

Route::put('update-device-token', [FcmController::class, 'updateDeviceToken']);
Route::post('send-fcm-notification', [FcmController::class, 'sendFcmNotification']);
Route::get('/testGetBranches', [TestController3::class, 'testGetBranches']);
Route::get('/generatePendingApprovalPreviousOrderDetailsReport', [TestController4::class, 'generatePendingApprovalPreviousOrderDetailsReport']);
Route::get('productsNotInventoried', [TestController4::class, 'missingProducts']);

Route::get('getStockSupplyReport', [TestController4::class, 'getStockSupplyReport']);

Route::get('analyticsBranchConsumption', [BranchConsumptionAnalysisController::class, 'analyze']);

Route::get('analyticsBranchConsumptionComparison', [BranchConsumptionAnalysisController::class, 'compare']);
