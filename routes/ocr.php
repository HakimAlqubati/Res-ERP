<?php

use App\Http\Controllers\AWS\Textract\OcrController;
use Illuminate\Support\Facades\Route; 

Route::post('/ocr', OcrController::class);
