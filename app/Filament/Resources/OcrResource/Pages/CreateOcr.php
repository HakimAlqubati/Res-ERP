<?php

namespace App\Filament\Resources\OcrResource\Pages;

use App\Filament\Resources\OcrResource;
use App\Services\GoogleVisionService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateOcr extends CreateRecord
{
    protected static string $resource = OcrResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // معالجة الصورة عبر Vision API
        $imagePath = Storage::disk('public')->path($data['image_path']);
        $visionService = new GoogleVisionService();
        $data['extracted_text'] = $visionService->detectText($imagePath);

        return $data;
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
