<?php

use App\Http\Controllers\TestController3;
use Illuminate\Support\Facades\Route;

Route::get('/custom-route', function () {
    return response()->json(['message' => 'Hello from custom route']);
});


Route::get('/testfifo', [TestController3::class, 'testFifo']);
