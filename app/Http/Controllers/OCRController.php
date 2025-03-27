<?php

namespace App\Http\Controllers;

use Codesmiths\LaravelOcrSpace\Facades\OcrSpace;
use Codesmiths\LaravelOcrSpace\OcrSpaceOptions;
use Illuminate\Http\Request;

class OCRController extends Controller
{
    public function test()
    {

        $filePath = storage_path('app/public/sample-image.jpg');

        $options = OcrSpaceOptions::make()
            ->language('en')
            ->isTable(true);

        $result = OcrSpace::parseImageFile($filePath, $options);

        $parsedText = $result->getParsedResults()[0]->getParsedText();

        return nl2br($parsedText); // عرض النص مع فواصل الأسطر

    }
}
