<?php

namespace App\Services\Ocr;

use thiagoalessio\TesseractOCR\TesseractOCR;

class TesseractService
{
    public function extractText($imagePath, $lang = 'eng')
    {
        putenv('PATH=' . getenv('PATH') . ';C:\Program Files\Tesseract-OCR');

        if (!file_exists($imagePath)) {
            throw new \Exception("Image not found at path: $imagePath");
        }

        return (new TesseractOCR($imagePath))
            ->lang('ara')
            ->psm(6) // أو جرّب 4 أو 11 أو 12
            ->run();
    }
}
